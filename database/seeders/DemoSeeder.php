<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Modules\Core\Models\User;
use Modules\Referentiel\Models\Article;
use Modules\Referentiel\Models\Categorie;
use Modules\Referentiel\Models\Direction;
use Modules\Referentiel\Models\Magasin;
use Modules\Referentiel\Models\ResponsableMagasin;
use Modules\Referentiel\Models\Service;
use Modules\Referentiel\Services\Parametres;
use Modules\Stock\Models\Document;
use Modules\Stock\Services\InventaireService;
use Modules\Stock\Services\MouvementService;
use Modules\Stock\Services\TransfertService;

/**
 * Données de démonstration (vidéo de présentation, formation) : CHU Yalgado Ouédraogo,
 * trois magasins, sept services, une quarantaine d'articles et trois mois d'historique
 * passés par les services métier (stocks, lots FEFO, transferts, inventaire cohérents).
 *
 * À lancer UNIQUEMENT sur une base vide dédiée à la démonstration :
 *   php artisan migrate:fresh --force && php artisan cores:sync
 *   php artisan db:seed --class='Modules\Referentiel\Database\Seeders\RolesSeeder' --force
 *   php artisan db:seed --class=DemoSeeder --force
 *
 * Comptes de démonstration (un par rôle), identifiants de test locaux :
 *   superadmin, admin, gestionnaire, operateur, consultant
 *   mot de passe : variable d'environnement DEMO_PASSWORD, sinon MOT_DE_PASSE_DEMO ci-dessous.
 */
class DemoSeeder extends Seeder
{
    public const MOT_DE_PASSE_DEMO = 'Demo-Chuyo-2026';

    protected MouvementService $mouvements;

    protected TransfertService $transferts;

    /** @var array<string, User> */
    protected array $u = [];

    /** @var array<string, Magasin> */
    protected array $m = [];

    /** @var array<string, Service> */
    protected array $s = [];

    /** @var array<string, Article> */
    protected array $a = [];

    protected int $numeroLot = 100;

    protected array $profils = [];

    protected int $semaine = -86;

    public function run(): void
    {
        abort_if(Document::exists(), 500, 'DemoSeeder : la base contient déjà des mouvements. Utilisez une base vide dédiée.');
        mt_srand(2026);

        $this->mouvements = app(MouvementService::class);
        $this->transferts = app(TransfertService::class);

        $this->utilisateurs();
        $this->referentiel();

        // L'historique remonte à 95 jours : on ouvre la saisie rétroactive le temps du seed.
        app(Parametres::class)->enregistrer([
            'mouvement_jours_retroactifs' => 120,
            'lots_actifs' => true,
            'transfert_mode' => 'DEUX_ETAPES',
            'peremption_alerte_jours' => 90,
        ], $this->u['admin']);

        $this->historique();

        app(Parametres::class)->enregistrer(['mouvement_jours_retroactifs' => 7], $this->u['admin']);
        $this->recalerHorodatages();
    }

    /**
     * Les services horodatent à l'instant du seed : on recale validation, réception et création
     * sur la date de chaque mouvement (heures de bureau), pour un historique crédible.
     */
    protected function recalerHorodatages(): void
    {
        foreach (Document::all() as $document) {
            $instant = $document->date_mouvement->copy()->setTime(mt_rand(7, 15), mt_rand(0, 59));
            if ($instant->isFuture()) {
                $instant = now()->subMinutes(mt_rand(20, 180));
            }
            $champs = ['created_at' => $instant, 'updated_at' => $instant];
            if ($document->validated_at) {
                $champs['validated_at'] = $instant->copy()->addMinutes(mt_rand(5, 40))->min(now());
            }
            if ($document->recu_at) {
                $champs['recu_at'] = $instant->copy()->addHours(mt_rand(2, 20))->min(now());
            }
            DB::table('documents')->where('id', $document->id)->update($champs);
            DB::table('activity_log')->where('subject_type', $document->getMorphClass())->where('subject_id', $document->id)
                ->update(['created_at' => $champs['validated_at'] ?? $instant, 'updated_at' => $champs['validated_at'] ?? $instant]);
        }
        // Mise en place du référentiel et des comptes : trois mois avant
        $debut = Carbon::today()->subDays(97)->setTime(8, 0);
        DB::table('activity_log')->where('created_at', '>=', Carbon::today())->where(function ($q) {
            $q->whereNull('subject_type')->orWhere('subject_type', '!=', (new Document)->getMorphClass());
        })->orderBy('id')->pluck('id')->each(function ($id, $i) use ($debut) {
            DB::table('activity_log')->where('id', $id)->update(['created_at' => $debut->copy()->addMinutes($i * 3), 'updated_at' => $debut->copy()->addMinutes($i * 3)]);
        });
        foreach (DB::table('inventaires')->get() as $inventaire) {
            $instant = Carbon::parse($inventaire->date_inventaire)->setTime(16, 30);
            DB::table('inventaires')->where('id', $inventaire->id)->update(['created_at' => $instant, 'updated_at' => $instant]);
        }
    }

    protected function utilisateurs(): void
    {
        $motDePasse = Hash::make(env('DEMO_PASSWORD', self::MOT_DE_PASSE_DEMO));
        $comptes = [
            'superadmin' => ['Issouf', 'SAWADOGO', 'Direction des systèmes d\'information', ['super-admin', 'admin']],
            'admin' => ['Aminata', 'KABORÉ', 'Direction des services financiers', ['admin']],
            'gestionnaire' => ['Boukary', 'ZONGO', 'Magasin central', ['gestionnaire']],
            'operateur' => ['Salimata', 'TRAORÉ', 'Urgences médicales', ['operateur']],
            'consultant' => ['Moussa', 'OUATTARA', 'Direction générale', ['consultant']],
        ];
        foreach ($comptes as $login => [$prenom, $nom, $service, $roles]) {
            $user = User::create([
                'name' => $prenom, 'last_name' => $nom, 'user_name' => $login,
                'email' => "{$login}@chu-yo.demo", 'service' => $service,
                'password' => $motDePasse, 'is_active' => true,
            ]);
            $user->syncRoles($roles);
            $this->u[$login] = $user;
        }
    }

    protected function referentiel(): void
    {
        foreach ([
            'central' => ['MAG-CEN', 'Magasin central', 'Bâtiment administratif, rez-de-chaussée'],
            'urgences' => ['MAG-URG', 'Magasin des Urgences', 'Service des urgences médicales'],
            'bloc' => ['MAG-BLO', 'Magasin du Bloc opératoire', 'Bloc opératoire central'],
        ] as $cle => [$code, $libelle, $lieu]) {
            $this->m[$cle] = Magasin::create(['code' => $code, 'libelle' => $libelle, 'localisation' => $lieu, 'actif' => true]);
        }

        $this->m['central']->utilisateurs()->attach($this->u['gestionnaire']);
        $this->m['urgences']->utilisateurs()->attach($this->u['operateur']);
        foreach ([['central', 'gestionnaire'], ['urgences', 'operateur']] as [$mag, $login]) {
            ResponsableMagasin::create([
                'magasin_id' => $this->m[$mag]->id, 'user_id' => $this->u[$login]->id,
                'role_responsable' => ResponsableMagasin::PRINCIPAL, 'date_debut' => now()->subYear()->toDateString(),
                'actif' => true, 'cree_par' => $this->u['admin']->id,
            ]);
        }

        $structures = [
            ['DSO', 'Direction des soins', [
                'urgences' => ['SUM', 'Urgences médicales'], 'pediatrie' => ['PED', 'Pédiatrie'],
                'gyneco' => ['GYO', 'Gynécologie-obstétrique'], 'medecine' => ['MED', 'Médecine interne'],
                'chirurgie' => ['CHG', 'Chirurgie générale'],
            ]],
            ['DMT', 'Direction médico-technique', [
                'labo' => ['LAB', 'Laboratoire'], 'radio' => ['RAD', 'Radiologie'],
            ]],
        ];
        foreach ($structures as [$code, $libelle, $services]) {
            $direction = Direction::create(['code' => $code, 'libelle' => $libelle, 'actif' => true]);
            foreach ($services as $cle => [$sCode, $sLibelle]) {
                $this->s[$cle] = Service::create(['code' => $sCode, 'libelle' => $sLibelle, 'direction_id' => $direction->id, 'actif' => true]);
            }
        }

        // [clé, code, désignation, unité, seuil minimum]
        $catalogue = [
            ['CMM', 'Consommables médicaux', 'Boîte', [
                ['gants', 'CM-001', 'Gants d\'examen non stériles, taille M (boîte de 100)', 'Boîte', 60],
                ['gants_l', 'CM-002', 'Gants d\'examen non stériles, taille L (boîte de 100)', 'Boîte', 40],
                ['gants_st', 'CM-003', 'Gants chirurgicaux stériles 7,5 (paire)', 'Paire', 200],
                ['ser5', 'CM-010', 'Seringues 5 ml avec aiguille (boîte de 100)', 'Boîte', 40],
                ['ser10', 'CM-011', 'Seringues 10 ml avec aiguille (boîte de 100)', 'Boîte', 30],
                ['ser2', 'CM-012', 'Seringues 2 ml avec aiguille (boîte de 100)', 'Boîte', 20],
                ['compresses', 'CM-020', 'Compresses stériles 10 × 10 (paquet de 10)', 'Paquet', 150],
                ['sparadrap', 'CM-021', 'Sparadrap perforé 5 cm × 5 m', 'Rouleau', 50],
                ['bande', 'CM-022', 'Bande de crêpe 10 cm', 'Rouleau', 60],
                ['coton', 'CM-023', 'Coton hydrophile 500 g', 'Rouleau', 20],
                ['catheter20', 'CM-030', 'Cathéter IV 20 G', 'Unité', 150],
                ['catheter22', 'CM-031', 'Cathéter IV 22 G', 'Unité', 120],
                ['perfuseur', 'CM-032', 'Perfuseur à prise d\'air', 'Unité', 200],
                ['sonde', 'CM-040', 'Sonde urinaire Foley CH16', 'Unité', 40],
                ['poche', 'CM-041', 'Poche à urine 2 L', 'Unité', 50],
                ['masques', 'CM-050', 'Masques chirurgicaux (boîte de 50)', 'Boîte', 40],
                ['calots', 'CM-051', 'Calots de bloc (paquet de 100)', 'Paquet', 10],
                ['fils', 'CM-060', 'Fil de suture résorbable 2/0', 'Unité', 60],
                ['lames', 'CM-061', 'Lames de bistouri n° 23 (boîte de 100)', 'Boîte', 5],
            ]],
            ['ANT', 'Antiseptiques et désinfectants', 'Flacon', [
                ['alcool', 'AS-001', 'Alcool à 70° (flacon de 1 L)', 'Flacon', 40],
                ['betadine', 'AS-002', 'Povidone iodée 10 % (flacon de 500 ml)', 'Flacon', 30],
                ['chlorhex', 'AS-003', 'Chlorhexidine alcoolique (flacon de 250 ml)', 'Flacon', 20],
                ['gel', 'AS-004', 'Gel hydroalcoolique (flacon de 500 ml)', 'Flacon', 50],
            ]],
            ['LAB', 'Réactifs et consommables de laboratoire', 'Boîte', [
                ['tubes', 'LB-001', 'Tubes EDTA 4 ml (boîte de 100)', 'Boîte', 10],
                ['bandelettes', 'LB-002', 'Bandelettes urinaires 10 paramètres', 'Boîte', 8],
                ['lames_micro', 'LB-003', 'Lames porte-objet (boîte de 50)', 'Boîte', 6],
            ]],
            ['ENT', 'Produits d\'entretien', 'Bidon', [
                ['javel', 'EN-001', 'Eau de Javel 12° (bidon de 5 L)', 'Bidon', 15],
                ['savon', 'EN-002', 'Savon liquide (bidon de 5 L)', 'Bidon', 10],
                ['sacs', 'EN-003', 'Sacs poubelle DASRI 50 L (rouleau)', 'Rouleau', 25],
            ]],
            ['BUR', 'Fournitures de bureau', 'Unité', [
                ['ramettes', 'FB-001', 'Ramette de papier A4 80 g', 'Ramette', 30],
                ['registres', 'FB-002', 'Registre de 200 pages', 'Unité', 5],
                ['stylos', 'FB-003', 'Stylos à bille bleus (boîte de 50)', 'Boîte', 4],
                ['cartouches', 'FB-004', 'Cartouche d\'encre laser noire', 'Unité', 3],
                ['chemises', 'FB-005', 'Chemises cartonnées (paquet de 100)', 'Paquet', 4],
            ]],
            ['IMA', 'Imagerie médicale', 'Boîte', [
                ['films', 'IM-001', 'Films radiographiques 35 × 43 (boîte de 100)', 'Boîte', 4],
                ['gel_echo', 'IM-002', 'Gel d\'échographie (flacon de 260 ml)', 'Flacon', 12],
            ]],
        ];
        foreach ($catalogue as [$code, $libelle, $unite, $articles]) {
            $categorie = Categorie::create(['code' => $code, 'libelle' => $libelle, 'unite_par_defaut' => $unite]);
            foreach ($articles as [$cle, $aCode, $designation, $uMesure, $seuil]) {
                $this->a[$cle] = Article::create([
                    'code' => $aCode, 'designation' => $designation, 'unite_mesure' => $uMesure,
                    'categorie_id' => $categorie->id, 'stock_minimum' => $seuil, 'actif' => true,
                    'cree_par' => $this->u['admin']->id,
                ]);
            }
        }

        foreach (['gants', 'ser5', 'ser10', 'compresses', 'catheter20', 'perfuseur', 'alcool', 'gel'] as $ordre => $cle) {
            DB::table('articles_frequents')->insert([
                'magasin_id' => $this->m['central']->id, 'article_id' => $this->a[$cle]->id, 'ordre' => $ordre + 1,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    protected function historique(): void
    {
        $g = $this->u['gestionnaire'];
        $op = $this->u['operateur'];

        // J-95 : dotation initiale du magasin central (lots aux péremptions variées)
        $this->entree('central', -95, [
            'gants' => [[400, 300], [200, 40]], 'gants_l' => [[250, 300]], 'gants_st' => [[1400, 420]],
            'ser5' => [[220, 500]], 'ser10' => [[160, 520]], 'ser2' => [[90, 480]],
            'compresses' => [[700, 380], [300, 25]], 'sparadrap' => [[220, 600]], 'bande' => [[260, 700]],
            'coton' => [[80, 700]], 'catheter20' => [[600, 540]], 'catheter22' => [[450, 540]],
            'perfuseur' => [[900, 610]], 'sonde' => [[160, 400]], 'poche' => [[200, 500]],
            'masques' => [[180, 330]], 'calots' => [[40, 700]], 'fils' => [[240, 300]], 'lames' => [[20, 900]],
            'alcool' => [[180, 365], [60, 15]], 'betadine' => [[130, 280]], 'chlorhex' => [[90, 50]], 'gel' => [[220, 200]],
            'tubes' => [[50, 60]], 'bandelettes' => [[40, 20]], 'lames_micro' => [[30, 900]],
            'javel' => [[70, 400]], 'savon' => [[50, 400]], 'sacs' => [[120, null]],
            'ramettes' => [[150, null]], 'registres' => [[30, null]], 'stylos' => [[20, null]],
            'cartouches' => [[14, null]], 'chemises' => [[25, null]], 'films' => [[25, 300]], 'gel_echo' => [[60, 400]],
        ], 'Livraison CAMEG — dotation du trimestre', $g);

        // J-90 : approvisionnement des magasins des Urgences et du Bloc
        $this->transfert('central', 'urgences', -90, ['gants' => 120, 'ser5' => 60, 'ser10' => 50, 'compresses' => 200,
            'catheter20' => 180, 'perfuseur' => 250, 'alcool' => 50, 'sparadrap' => 60, 'gel' => 60], $g, recu: 0);
        $this->transfert('central', 'bloc', -89, ['gants_st' => 400, 'compresses' => 150, 'fils' => 120, 'lames' => 8,
            'betadine' => 50, 'calots' => 20, 'masques' => 60, 'sonde' => 50], $g, recu: 0);

        // Consommation des services, semaine après semaine
        $profils = [
            'urgences' => ['gants' => 14, 'ser5' => 6, 'ser10' => 7, 'compresses' => 30, 'catheter20' => 25, 'catheter22' => 10, 'perfuseur' => 40, 'alcool' => 6, 'sparadrap' => 8, 'gel' => 7],
            'pediatrie' => ['gants' => 8, 'ser2' => 5, 'ser5' => 4, 'catheter22' => 18, 'perfuseur' => 15, 'compresses' => 12, 'coton' => 2, 'alcool' => 3],
            'gyneco' => ['gants' => 10, 'gants_st' => 40, 'compresses' => 20, 'sonde' => 6, 'poche' => 8, 'betadine' => 4, 'fils' => 10],
            'medecine' => ['gants' => 7, 'ser5' => 5, 'ser10' => 6, 'perfuseur' => 25, 'catheter20' => 15, 'bande' => 6, 'poche' => 6],
            'chirurgie' => ['gants_st' => 30, 'compresses' => 25, 'bande' => 10, 'sparadrap' => 6, 'fils' => 8, 'betadine' => 3, 'chlorhex' => 3],
            'labo' => ['gants' => 6, 'tubes' => 4, 'bandelettes' => 3, 'ser5' => 2, 'javel' => 2],
            'radio' => ['films' => 2, 'gel_echo' => 4, 'ramettes' => 3],
        ];
        $this->profils = $profils;
        $this->consommerJusqua(-60);

        // J-60 : réassort des Urgences ; J-45 : deuxième livraison au central
        $this->transfert('central', 'urgences', -60, ['gants' => 60, 'compresses' => 120, 'perfuseur' => 150, 'catheter20' => 100, 'ser10' => 30], $g, recu: 0);
        $this->consommerJusqua(-45);
        $this->entree('central', -45, [
            'gants' => [[300, 330]], 'compresses' => [[500, 400]], 'perfuseur' => [[500, 640]], 'catheter20' => [[300, 560]],
            'ser5' => [[150, 520]], 'gants_st' => [[600, 450]], 'fils' => [[120, 320]], 'masques' => [[100, 360]], 'gel' => [[120, 230]], 'alcool' => [[100, 420]],
        ], 'Livraison CAMEG — commande complémentaire', $g);

        $this->consommerJusqua(-40);
        // Annulation d'une sortie saisie sur le mauvais service
        if ($erreur = $this->sortie('central', -40, 'radio', ['perfuseur' => 30], $g)) {
            $this->mouvements->annuler($erreur, $this->u['admin']);
        }

        $this->consommerJusqua(-30);
        // Casse constatée au central
        $this->mouvements->creerAjustement($this->m['central']->id, $this->date(-30), [$this->a['alcool']->id => -4],
            'Casse : 4 flacons brisés lors du rangement', $g);

        $this->consommerJusqua(-20);
        // Transfert reçu avec écart (réintégré au central), puis réassort du Bloc
        $this->transfert('central', 'urgences', -20, ['gants' => 50, 'ser5' => 30, 'compresses' => 80], $g, recu: ['gants' => 46],
            motif: '4 boîtes de gants endommagées pendant le transport');
        $this->transfert('central', 'bloc', -18, ['gants_st' => 200, 'compresses' => 100, 'fils' => 40], $g, recu: 0);

        $this->consommerJusqua(-10);
        // Inventaire du Bloc opératoire avec écarts
        $comptages = array_filter($this->mouvements->stocksDisponibles($this->m['bloc']->id, Article::pluck('id')->all()), fn ($q) => $q > 0);
        $comptages[$this->a['compresses']->id] -= 6;
        $comptages[$this->a['fils']->id] -= 3;
        $comptages[$this->a['gants_st']->id] += 10;
        app(InventaireService::class)->valider($this->m['bloc'], $this->date(-10), $comptages,
            'Inventaire trimestriel du Bloc opératoire', $g);

        $this->consommerJusqua(0);
        // Mouvements récents et du jour
        $this->sortie('central', -1, 'pediatrie', ['gants' => 6, 'catheter22' => 20, 'compresses' => 10], $g);
        $this->sortie('central', 0, 'urgences', ['ser10' => 4, 'perfuseur' => 20], $g);
        $this->entree('central', 0, ['gel' => [[80, 260]], 'masques' => [[60, 380]]], 'Don du partenaire technique', $g);

        // En transit vers le Bloc (non reçu) et un brouillon en cours
        $this->transfert('central', 'bloc', -1, ['gants_st' => 100, 'betadine' => 20, 'lames' => 4], $g, recu: null);
        $this->mouvements->enregistrerBrouillon(Document::SORTIE, [
            'date_mouvement' => $this->date(0), 'magasin_id' => $this->m['central']->id,
            'direction_id' => $this->s['labo']->direction_id, 'service_id' => $this->s['labo']->id,
            'lignes' => [['article_id' => $this->a['tubes']->id, 'quantite' => 3]],
            'commentaire' => 'À compléter après accord du chef de service',
        ], $g);
    }

    /** Consommation hebdomadaire des services jusqu'au jour donné (exclu), dans l'ordre chronologique. */
    protected function consommerJusqua(int $jourMax): void
    {
        $g = $this->u['gestionnaire'];
        $op = $this->u['operateur'];
        for (; $this->semaine < $jourMax && $this->semaine <= -2; $this->semaine += 7) {
            $jour = $this->semaine;
            foreach ($this->profils as $service => $profil) {
                $lignes = [];
                foreach ($profil as $article => $base) {
                    if (mt_rand(1, 100) <= 80) {
                        $lignes[$article] = max(1, (int) round($base * mt_rand(70, 135) / 100));
                    }
                }
                $magasin = $service === 'urgences' ? 'urgences' : 'central';
                $this->sortie($magasin, min($jour + mt_rand(0, 1), $jourMax - 1), $service, $lignes, $magasin === 'urgences' ? $op : $g);
            }
            // Fournitures administratives, une semaine sur trois
            if ($jour % 3 === 0) {
                $this->sortie('central', min($jour + 1, $jourMax - 1), 'medecine', ['ramettes' => mt_rand(4, 8), 'stylos' => 1], $g);
            }
        }
    }

    protected function date(int $jour): string
    {
        return Carbon::today()->addDays(min(0, $jour))->toDateString();
    }

    /** $lignes : [article => [[quantité, jours avant péremption (null = sans lot)], …]] */
    protected function entree(string $magasin, int $jour, array $lignes, string $commentaire, User $user): Document
    {
        $donnees = ['date_mouvement' => $this->date($jour), 'magasin_id' => $this->m[$magasin]->id, 'commentaire' => $commentaire, 'lignes' => []];
        foreach ($lignes as $article => $lots) {
            foreach ($lots as [$quantite, $peremption]) {
                $donnees['lignes'][] = ['article_id' => $this->a[$article]->id, 'quantite' => $quantite] + ($peremption === null ? [] : [
                    'lot_numero' => 'L26-'.str_pad((string) $this->numeroLot++, 4, '0', STR_PAD_LEFT),
                    'date_peremption' => Carbon::today()->addDays($peremption)->toDateString(),
                ]);
            }
        }

        return $this->mouvements->valider($this->mouvements->enregistrerBrouillon(Document::ENTREE, $donnees, $user), $user);
    }

    protected function sortie(string $magasin, int $jour, string $service, array $lignes, User $user): ?Document
    {
        $disponibles = $this->mouvements->stocksDisponibles($this->m[$magasin]->id, array_map(fn ($k) => $this->a[$k]->id, array_keys($lignes)));
        $donnees = [
            'date_mouvement' => $this->date($jour), 'magasin_id' => $this->m[$magasin]->id,
            'direction_id' => $this->s[$service]->direction_id, 'service_id' => $this->s[$service]->id, 'lignes' => [],
        ];
        foreach ($lignes as $article => $quantite) {
            $quantite = min($quantite, $disponibles[$this->a[$article]->id]);
            if ($quantite > 0) {
                $donnees['lignes'][] = ['article_id' => $this->a[$article]->id, 'quantite' => $quantite];
            }
        }
        if (! $donnees['lignes']) {
            return null;
        }

        try {
            return $this->mouvements->valider($this->mouvements->enregistrerBrouillon(Document::SORTIE, $donnees, $user), $user);
        } catch (ValidationException) {
            return null;
        }
    }

    /** $recu : null = reste en transit, 0 = reçu en totalité, [article => quantité reçue] = reçu avec écart */
    protected function transfert(string $de, string $vers, int $jour, array $lignes, User $user, array|int|null $recu, ?string $motif = null): Document
    {
        $disponibles = $this->mouvements->stocksDisponibles($this->m[$de]->id, array_map(fn ($k) => $this->a[$k]->id, array_keys($lignes)));
        foreach ($lignes as $article => $quantite) {
            $lignes[$article] = min($quantite, $disponibles[$this->a[$article]->id]);
        }
        $lignes = array_filter($lignes);
        $document = $this->transferts->expedier($this->mouvements->enregistrerBrouillon(Document::TRANSFERT, [
            'date_mouvement' => $this->date($jour), 'magasin_id' => $this->m[$de]->id, 'magasin_destination_id' => $this->m[$vers]->id,
            'lignes' => collect($lignes)->map(fn ($q, $k) => ['article_id' => $this->a[$k]->id, 'quantite' => $q])->values()->all(),
        ], $user), $user);

        if ($recu !== null) {
            $quantites = is_array($recu) ? collect($recu)->mapWithKeys(fn ($q, $k) => [$this->a[$k]->id => $q])->all() : [];
            $this->transferts->receptionner($document, $quantites, $motif, $vers === 'urgences' ? $this->u['operateur'] : $user);
        }

        return $document;
    }
}
