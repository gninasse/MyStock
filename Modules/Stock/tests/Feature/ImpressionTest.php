<?php

namespace Modules\Stock\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Modules\Core\Models\User;
use Modules\Core\Services\PdfService;
use Modules\Referentiel\Database\Seeders\RolesSeeder;
use Modules\Referentiel\Models\Article;
use Modules\Referentiel\Models\Categorie;
use Modules\Referentiel\Models\Magasin;
use Modules\Stock\Models\Document;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

/**
 * Bons imprimables des documents : aperçu en ligne, téléchargement, droits, journalisation.
 */
class ImpressionTest extends TestCase
{
    use RefreshDatabase;

    protected Magasin $magasin;

    protected Magasin $autre;

    protected Article $papier;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-05-22 10:00:00');
        $this->seed(RolesSeeder::class);

        $this->magasin = Magasin::create(['code' => 'MAG-001', 'libelle' => 'Magasin Central']);
        $this->autre = Magasin::create(['code' => 'MAG-002', 'libelle' => 'Magasin Annexe']);
        $categorie = Categorie::create(['code' => 'FOUR', 'libelle' => 'Fournitures']);
        $this->papier = Article::create(['code' => 'ART-001', 'designation' => 'Papier A4', 'unite_mesure' => 'RAME', 'categorie_id' => $categorie->id]);
    }

    protected function gestionnaire(Magasin ...$magasins): User
    {
        $user = User::factory()->create()->assignRole('gestionnaire');
        foreach ($magasins as $magasin) {
            $magasin->utilisateurs()->attach($user);
        }

        return $user;
    }

    protected function entreeValidee(User $user): Document
    {
        $this->actingAs($user)->postJson(route('stock.saisie.store', 'entrees'), [
            'action' => 'valider', 'date_mouvement' => '2026-05-22', 'magasin_id' => $this->magasin->id,
            'lignes' => [['article_id' => $this->papier->id, 'quantite' => 12]],
        ])->assertOk();

        return Document::where('type', Document::ENTREE)->firstOrFail();
    }

    public function test_bons_et_fiches_portent_l_en_tete_officiel(): void
    {
        // Le HTML du gabarit à la place du PDF, pour en lire le contenu
        $this->app->instance(PdfService::class, new class extends PdfService
        {
            public function rendre(string $vue, array $donnees, string $nomFichier, string $orientation = 'portrait'): Response
            {
                return response(view($vue, $donnees)->render());
            }
        });
        $user = $this->gestionnaire($this->magasin);
        $document = $this->entreeValidee($user);

        $this->actingAs($user)->get(route('stock.documents.imprimer', $document->id))->assertOk()
            ->assertSee('BURKINA FASO')
            ->assertSee('CENTRE HOSPITALIER UNIVERSITAIRE YALGADO OUÉDRAOGO')
            ->assertSee("Bon d'entrée")
            ->assertSee('N° '.$document->reference)
            ->assertSee('Fait à Ouagadougou, le 22 mai 2026');

        $this->get(route('stock.inventaires.fiche-comptage', ['magasin_id' => $this->magasin->id]))->assertOk()
            ->assertSee('BURKINA FASO')
            ->assertSee('Fiche de comptage')
            ->assertSee('Fait à Ouagadougou, le ........');
    }

    public function test_bon_affiche_en_ligne_et_journalise(): void
    {
        $user = $this->gestionnaire($this->magasin);
        $document = $this->entreeValidee($user);

        $reponse = $this->actingAs($user)->get(route('stock.documents.imprimer', $document->id));

        $reponse->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('inline', $reponse->headers->get('content-disposition'));
        $this->assertTrue(Activity::where('description', 'document_imprime')->where('subject_id', $document->id)->exists());
    }

    public function test_bon_telechargeable(): void
    {
        $user = $this->gestionnaire($this->magasin);
        $document = $this->entreeValidee($user);

        $reponse = $this->actingAs($user)->get(route('stock.documents.imprimer', [$document->id, 'telecharger' => 1]));

        $reponse->assertOk();
        $this->assertStringStartsWith('attachment', $reponse->headers->get('content-disposition'));
        $this->assertStringContainsString(strtolower($document->reference), $reponse->headers->get('content-disposition'));
    }

    public function test_bon_refuse_hors_perimetre(): void
    {
        $document = $this->entreeValidee($this->gestionnaire($this->magasin));

        $this->actingAs($this->gestionnaire($this->autre))
            ->get(route('stock.documents.imprimer', $document->id))
            ->assertForbidden();
    }

    public function test_bon_de_transfert_imprimable_par_le_destinataire(): void
    {
        $expediteur = $this->gestionnaire($this->magasin);
        $this->entreeValidee($expediteur);
        $this->actingAs($expediteur)->postJson(route('stock.saisie.store', 'transferts'), [
            'action' => 'valider', 'date_mouvement' => '2026-05-22', 'magasin_id' => $this->magasin->id,
            'magasin_destination_id' => $this->autre->id, 'lignes' => [['article_id' => $this->papier->id, 'quantite' => 5]],
        ])->assertOk();
        $transfert = Document::where('type', Document::TRANSFERT)->firstOrFail();

        $this->actingAs($this->gestionnaire($this->autre))
            ->get(route('stock.documents.imprimer', $transfert->id))
            ->assertOk()->assertHeader('content-type', 'application/pdf');
    }

    public function test_fiche_document_propose_l_impression_en_modale(): void
    {
        $user = $this->gestionnaire($this->magasin);
        $document = $this->entreeValidee($user);

        $this->actingAs($user)->get(route('stock.documents.show', $document->id))
            ->assertOk()
            ->assertSee('data-apercu-pdf="'.route('stock.documents.imprimer', $document->id).'"', false)
            ->assertSee('id="modalApercuPdf"', false);
    }

    public function test_fiche_de_comptage_a_l_aveugle_par_defaut(): void
    {
        $user = $this->gestionnaire($this->magasin);
        $this->entreeValidee($user);

        $reponse = $this->actingAs($user)->get(route('stock.inventaires.fiche-comptage', ['magasin_id' => $this->magasin->id]));

        $reponse->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertTrue(Activity::where('description', 'fiche_comptage_imprimee')->exists());
        $this->actingAs($this->gestionnaire($this->autre))
            ->get(route('stock.inventaires.fiche-comptage', ['magasin_id' => $this->magasin->id]))
            ->assertForbidden();
    }

    public function test_proces_verbal_d_inventaire(): void
    {
        $user = $this->gestionnaire($this->magasin);
        $this->entreeValidee($user);
        $id = $this->actingAs($user)->postJson(route('stock.inventaires.store'), [
            'magasin_id' => $this->magasin->id, 'date_inventaire' => '2026-05-22', 'commentaire' => 'Casse',
            'comptages' => [['article_id' => $this->papier->id, 'quantite_reelle' => 10]],
        ])->assertOk()->json('data.id');

        $this->actingAs($user)->get(route('stock.inventaires.imprimer', $id))
            ->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->actingAs($user)->get(route('stock.inventaires.show', $id))
            ->assertSee('data-apercu-pdf="'.route('stock.inventaires.imprimer', $id).'"', false);
        $this->actingAs($this->gestionnaire($this->autre))
            ->get(route('stock.inventaires.imprimer', $id))
            ->assertForbidden();
    }
}
