<?php

namespace Modules\Stock\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Modules\Core\Models\User;
use Modules\Referentiel\Database\Seeders\RolesSeeder;
use Modules\Referentiel\Models\Article;
use Modules\Referentiel\Models\Categorie;
use Modules\Referentiel\Models\Magasin;
use Modules\Referentiel\Services\Parametres;
use Modules\Stock\Models\Document;
use Modules\Stock\Models\Stock;
use Modules\Stock\Services\TransfertService;
use Tests\TestCase;

/**
 * Les quatre cas de transfert (normal, partiel, retour à la source, annulation)
 * et le paramétrage en une ou deux étapes.
 */
class TransfertTest extends TestCase
{
    use RefreshDatabase;

    protected Magasin $source;

    protected Magasin $destination;

    protected Article $papier;

    protected User $expediteur;

    protected User $recepteur;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-05-22 10:00:00');
        $this->seed(RolesSeeder::class);

        $this->source = Magasin::create(['code' => 'MAG-A', 'libelle' => 'Magasin A']);
        $this->destination = Magasin::create(['code' => 'MAG-B', 'libelle' => 'Magasin B']);
        $categorie = Categorie::create(['code' => 'FOUR', 'libelle' => 'Fournitures']);
        $this->papier = Article::create(['code' => 'ART-001', 'designation' => 'Papier A4', 'unite_mesure' => 'RAME', 'categorie_id' => $categorie->id]);

        $this->expediteur = $this->utilisateur([$this->source]);
        $this->recepteur = $this->utilisateur([$this->destination]);
        Stock::create(['magasin_id' => $this->source->id, 'article_id' => $this->papier->id, 'quantite' => 50]);
        Stock::create(['magasin_id' => $this->destination->id, 'article_id' => $this->papier->id, 'quantite' => 10]);
    }

    protected function utilisateur(array $magasins): User
    {
        $user = User::factory()->create()->assignRole('gestionnaire');
        foreach ($magasins as $magasin) {
            $magasin->utilisateurs()->attach($user);
        }

        return $user;
    }

    protected function parametrer(array $valeurs): void
    {
        app(Parametres::class)->enregistrer($valeurs, User::factory()->create());
    }

    protected function expedier(User $user, int $quantite = 20, array $surcharge = [])
    {
        return $this->actingAs($user)->postJson(route('stock.saisie.store', 'transferts'), $surcharge + [
            'action' => 'valider',
            'date_mouvement' => '2026-05-22',
            'magasin_id' => $this->source->id,
            'magasin_destination_id' => $this->destination->id,
            'lignes' => [['article_id' => $this->papier->id, 'quantite' => $quantite]],
        ]);
    }

    protected function stock(Magasin $magasin): int
    {
        return Stock::where('magasin_id', $magasin->id)->where('article_id', $this->papier->id)->value('quantite');
    }

    protected function transfert(): Document
    {
        return Document::where('type', Document::TRANSFERT)->firstOrFail();
    }

    public function test_cas_1_transfert_complet_en_deux_etapes(): void
    {
        $this->expedier($this->expediteur)->assertOk()->assertJsonPath('data.statut', Document::EN_TRANSIT);

        $transfert = $this->transfert();
        $this->assertStringStartsWith('TRF-20260522-', $transfert->reference);
        $this->assertSame([30, 10], [$this->stock($this->source), $this->stock($this->destination)]);

        app(TransfertService::class)->receptionner($transfert, [$this->papier->id => 20], null, $this->recepteur);

        $this->assertSame([30, 30], [$this->stock($this->source), $this->stock($this->destination)]);
        $this->assertSame(Document::VALIDE, $transfert->fresh()->statut);
        $this->assertSame($this->recepteur->id, $transfert->fresh()->recu_par);
        $reception = Document::where('type', Document::RECEPTION)->firstOrFail();
        $this->assertSame($transfert->id, $reception->document_origine_id);
        $this->assertSame($this->destination->id, $reception->magasin_id);
    }

    public function test_cas_2_reception_partielle_reintegree_a_la_source(): void
    {
        $this->expedier($this->expediteur);
        $service = app(TransfertService::class);

        try {
            $service->receptionner($this->transfert(), [$this->papier->id => 15], '', $this->recepteur);
            $this->fail('Le motif aurait dû être exigé.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('motif', $e->getMessage());
        }

        $service->receptionner($this->transfert(), [$this->papier->id => 15], '5 rames abîmées', $this->recepteur);

        $this->assertSame([35, 25], [$this->stock($this->source), $this->stock($this->destination)]);
        $this->assertSame(15, $this->transfert()->lignes->first()->quantite_recue);
        $this->assertSame('5 rames abîmées', $this->transfert()->motif_ecart);
        $this->assertDatabaseHas('documents', ['type' => Document::REINTEGRATION, 'magasin_id' => $this->source->id, 'document_origine_id' => $this->transfert()->id]);
    }

    public function test_ecart_compte_comme_perte_si_parametre(): void
    {
        $this->parametrer(['transfert_ecart' => 'PERTE']);
        $this->expedier($this->expediteur);

        app(TransfertService::class)->receptionner($this->transfert(), [$this->papier->id => 15], 'Colis endommagé', $this->recepteur);

        $this->assertSame([30, 25], [$this->stock($this->source), $this->stock($this->destination)]);
        $this->assertDatabaseMissing('documents', ['type' => Document::REINTEGRATION]);
    }

    public function test_quantite_recue_superieure_a_l_expedition_refusee(): void
    {
        $this->expedier($this->expediteur);

        $this->expectException(ValidationException::class);
        app(TransfertService::class)->receptionner($this->transfert(), [$this->papier->id => 21], null, $this->recepteur);
    }

    public function test_cas_4_annulation_avant_reception_restitue_tout(): void
    {
        $this->expedier($this->expediteur);
        $transfert = $this->transfert();

        $this->actingAs($this->expediteur)->postJson(route('stock.documents.annuler-transfert', $transfert->id))->assertOk();

        $this->assertSame([50, 10], [$this->stock($this->source), $this->stock($this->destination)]);
        $this->assertSame(Document::ANNULE, $transfert->fresh()->statut);

        // Un transfert annulé ne peut plus être réceptionné
        $this->expectException(ValidationException::class);
        app(TransfertService::class)->receptionner($transfert, [], null, $this->recepteur);
    }

    public function test_une_etape_si_l_expediteur_a_acces_aux_deux_magasins(): void
    {
        $this->parametrer(['transfert_mode' => 'UNE_ETAPE_SI_ACCES']);

        $this->expedier($this->expediteur, 5)->assertJsonPath('data.statut', Document::EN_TRANSIT);

        $deuxMagasins = $this->utilisateur([$this->source, $this->destination]);
        $this->expedier($deuxMagasins, 5)->assertJsonPath('data.statut', Document::VALIDE);

        $this->assertSame([40, 15], [$this->stock($this->source), $this->stock($this->destination)]);
    }

    public function test_une_etape_pour_tous(): void
    {
        $this->parametrer(['transfert_mode' => 'UNE_ETAPE']);

        $this->expedier($this->expediteur)->assertOk()->assertJsonPath('data.statut', Document::VALIDE);

        $this->assertSame([30, 30], [$this->stock($this->source), $this->stock($this->destination)]);
    }

    public function test_controles_a_l_expedition(): void
    {
        $this->expedier($this->expediteur, 51)->assertStatus(422)->assertJsonValidationErrors('lignes');
        $this->expedier($this->expediteur, 5, ['magasin_destination_id' => $this->source->id])
            ->assertStatus(422)->assertJsonValidationErrors('magasin_destination_id');
        $this->expedier($this->expediteur, 5, ['magasin_destination_id' => null])
            ->assertStatus(422)->assertJsonValidationErrors('magasin_destination_id');

        $this->assertSame(50, $this->stock($this->source));
    }

    public function test_visible_du_destinataire_et_pas_des_autres(): void
    {
        $this->expedier($this->expediteur);
        $transfert = $this->transfert();

        $this->actingAs($this->recepteur)->get(route('stock.documents.show', $transfert->id))
            ->assertOk()->assertSee('En transit')->assertDontSee('Annuler le transfert');
        $this->actingAs($this->recepteur)->getJson(route('stock.documents.data', ['type' => 'TRANSFERT']))
            ->assertJsonPath('total', 1)->assertJsonPath('rows.0.beneficiaire', '→ Magasin B');

        $this->actingAs($this->expediteur)->get(route('stock.documents.show', $transfert->id))
            ->assertOk()->assertSee('Annuler le transfert');

        $autre = $this->utilisateur([Magasin::create(['code' => 'MAG-C', 'libelle' => 'Magasin C'])]);
        $this->actingAs($autre)->get(route('stock.documents.show', $transfert->id))->assertForbidden();
    }

    public function test_formulaire_de_transfert(): void
    {
        $this->actingAs($this->expediteur)->get(route('stock.saisie.create', 'transferts'))
            ->assertOk()->assertSee('Magasin destinataire')->assertSee('Expédier le transfert');

        $operateur = User::factory()->create()->assignRole('operateur');
        $this->actingAs($operateur)->get(route('stock.saisie.create', 'transferts'))->assertForbidden();
    }

    public function test_ecrans_de_reception(): void
    {
        $this->expedier($this->expediteur);
        $transfert = $this->transfert();

        $this->actingAs($this->recepteur)->get(route('stock.transferts.a-receptionner'))
            ->assertOk()->assertSee($transfert->reference)->assertSee('Réceptionner');
        $this->get(route('stock.documents.show', $transfert->id))->assertSee('Réceptionner');
        $this->get(route('stock.transferts.reception', $transfert->id))
            ->assertOk()->assertSee('Quantités reçues')->assertSee('réintégrée dans le stock de Magasin A');

        // L'expéditeur n'a pas accès au magasin destinataire
        $this->actingAs($this->expediteur)->get(route('stock.transferts.a-receptionner'))->assertOk()->assertDontSee($transfert->reference);
        $this->get(route('stock.transferts.reception', $transfert->id))->assertForbidden();
    }

    public function test_reception_partielle_par_formulaire(): void
    {
        $this->expedier($this->expediteur);
        $transfert = $this->transfert();
        $url = route('stock.transferts.receptionner', $transfert->id);

        $this->actingAs($this->recepteur)->postJson($url, ['quantites' => [$this->papier->id => 12]])
            ->assertStatus(422)->assertJsonValidationErrors('lignes');

        $this->postJson($url, ['quantites' => [$this->papier->id => 12], 'motif' => '8 rames manquantes'])
            ->assertOk()->assertJsonPath('success', true);

        $this->assertSame([38, 22], [$this->stock($this->source), $this->stock($this->destination)]);

        // Déjà réceptionné : plus accessible
        $this->get(route('stock.transferts.reception', $transfert->id))->assertStatus(409);
        $this->get(route('stock.documents.show', $transfert->id))->assertSee('Reçu')->assertSee('8 rames manquantes');
    }

    public function test_refus_complet(): void
    {
        $this->expedier($this->expediteur);

        $this->actingAs($this->recepteur)
            ->postJson(route('stock.transferts.receptionner', $this->transfert()->id), ['quantites' => [$this->papier->id => 0], 'motif' => 'Commande erronée'])
            ->assertOk();

        $this->assertSame([50, 10], [$this->stock($this->source), $this->stock($this->destination)]);
        $this->assertDatabaseMissing('documents', ['type' => Document::RECEPTION]);
    }

    protected function retourner(User $user, Document $origine, int $quantite)
    {
        return $this->actingAs($user)->postJson(route('stock.saisie.store', 'transferts'), [
            'action' => 'valider',
            'date_mouvement' => '2026-05-22',
            'magasin_id' => $this->destination->id,
            'magasin_destination_id' => $this->source->id,
            'document_origine_id' => $origine->id,
            'lignes' => [['article_id' => $this->papier->id, 'quantite' => $quantite]],
        ]);
    }

    public function test_cas_3_retour_apres_reception(): void
    {
        $this->expedier($this->expediteur);
        $origine = $this->transfert();
        app(TransfertService::class)->receptionner($origine, [$this->papier->id => 20], null, $this->recepteur);

        // Formulaire pré-rempli depuis la fiche du transfert reçu
        $this->actingAs($this->recepteur)->get(route('stock.documents.show', $origine->id))->assertSee('Retourner au magasin source');
        $this->get(route('stock.documents.retour', $origine->id))->assertOk()
            ->assertSee('Retour du transfert')->assertSee('&quot;quantite&quot;:20', false);

        $this->retourner($this->recepteur, $origine, 21)->assertStatus(422)->assertJsonValidationErrors('lignes');
        $this->retourner($this->recepteur, $origine, 5)->assertOk()->assertJsonPath('data.statut', Document::EN_TRANSIT);

        $retour = Document::where('document_origine_id', $origine->id)->where('type', Document::TRANSFERT)->firstOrFail();
        app(TransfertService::class)->receptionner($retour, [$this->papier->id => 5], null, $this->expediteur);

        $this->assertSame([35, 25], [$this->stock($this->source), $this->stock($this->destination)]);

        // Il ne reste que 15 à retourner
        $this->retourner($this->recepteur, $origine, 16)->assertStatus(422);
        $this->retourner($this->recepteur, $origine, 15)->assertOk();
        $this->get(route('stock.documents.retour', $origine->id))->assertStatus(409);
    }

    public function test_retour_impossible_depuis_un_autre_magasin(): void
    {
        $this->expedier($this->expediteur);
        $origine = $this->transfert();
        app(TransfertService::class)->receptionner($origine, [$this->papier->id => 20], null, $this->recepteur);

        // L'expéditeur n'a pas accès au magasin destinataire : pas de retour
        $this->actingAs($this->expediteur)->get(route('stock.documents.retour', $origine->id))->assertForbidden();
    }

    public function test_etats_et_tableau_de_bord(): void
    {
        $this->expedier($this->expediteur);
        $transfert = $this->transfert();

        // En transit : visible des deux côtés
        $this->actingAs($this->recepteur)->get(route('etats.transferts'))
            ->assertOk()->assertSee($transfert->reference)->assertSee('En transit');
        $this->get(route('etats.tableau-de-bord'))->assertOk()->assertSee('Transferts en transit')->assertSee($transfert->reference);

        app(TransfertService::class)->receptionner($transfert, [$this->papier->id => 15], 'Casse', $this->recepteur);

        // Reçu avec écart : absent du filtre par défaut, présent avec « Tous »
        $this->get(route('etats.transferts'))->assertDontSee($transfert->reference);
        $this->get(route('etats.transferts', ['envoye' => 1, 'statut' => '']))->assertSee($transfert->reference)->assertSee('Casse');

        // Fiche de stock du destinataire : réception en provenance de A
        $this->get(route('etats.fiche-stock', ['article_id' => $this->papier->id, 'magasin_id' => $this->destination->id, 'date_du' => '2026-05-01']))
            ->assertOk()->assertSee('Réception de transfert')->assertSee('← Magasin A');

        // Journal de l'expéditeur : transfert vers B et réintégration de l'écart
        $this->actingAs($this->expediteur)->get(route('etats.journal'))
            ->assertOk()->assertSee('→ Magasin B')->assertSee('Réintégration')->assertSee('↩ '.$transfert->reference);
    }
}
