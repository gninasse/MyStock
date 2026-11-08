<?php

namespace Modules\Referentiel\Tests\Feature;

use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Core\Services\PdfService;
use Modules\Referentiel\Services\EnteteDocuments;
use Modules\Referentiel\Services\Parametres;

/**
 * En-tête officiel des documents PDF : paramétrage, images, droits, rendu.
 */
class EnteteDocumentsTest extends ReferentielTestCase
{
    /**
     * Remplace le rendu PDF par le HTML du gabarit, pour pouvoir en lire le contenu.
     */
    protected function capturerHtml(): void
    {
        $this->app->instance(PdfService::class, new class extends PdfService
        {
            public function rendre(string $vue, array $donnees, string $nomFichier, string $orientation = 'portrait'): Response
            {
                return response(view($vue, $donnees)->render());
            }
        });
    }

    public function test_en_tete_par_defaut_du_chu_yo(): void
    {
        $this->capturerHtml();

        $this->actingAs($this->utilisateur('admin'))
            ->get(route('referentiel.parametres.entete.apercu'))
            ->assertOk()
            ->assertSeeInOrder(['MINISTÈRE DE LA SANTÉ', 'SECRÉTARIAT GÉNÉRAL', 'CENTRE HOSPITALIER UNIVERSITAIRE YALGADO OUÉDRAOGO', 'DIRECTION GÉNÉRALE'])
            ->assertSee('BURKINA FASO')
            ->assertSee('La Patrie ou la Mort, nous Vaincrons')
            ->assertSee('Bon de transfert')
            ->assertSee('Fait à Ouagadougou, le')
            ->assertDontSee('data:image', false);
    }

    public function test_enregistrement_puis_rendu(): void
    {
        $this->capturerHtml();
        $admin = $this->utilisateur('admin');

        $this->actingAs($admin)->get(route('referentiel.parametres.entete'))
            ->assertOk()->assertSee('Hiérarchie administrative')->assertSee('CENTRE HOSPITALIER UNIVERSITAIRE YALGADO OUÉDRAOGO');

        $this->putJson(route('referentiel.parametres.entete.update'), [
            'entete_hierarchie' => ['MINISTÈRE DE LA SANTÉ', '  ', 'CHU-YO', 'DIRECTION DES SERVICES PHARMACEUTIQUES'],
            'entete_reference' => 'N° {numero}/CHU-YO/DG',
            'entete_lieu' => 'Ouagadougou',
            'entete_pied' => '03 BP 0000 Ouagadougou 03',
            'entete_libelles' => ['TRANSFERT' => 'Bordereau de transfert', 'SORTIE' => ''],
        ])->assertOk()->assertJson(['message' => 'En-tête des documents enregistré.']);

        // Lignes vides retirées, libellé vide = libellé par défaut
        $this->assertSame(['MINISTÈRE DE LA SANTÉ', 'CHU-YO', 'DIRECTION DES SERVICES PHARMACEUTIQUES'], Parametres::get('entete_hierarchie'));
        $entete = app(EnteteDocuments::class);
        $this->assertSame('Bordereau de transfert', $entete->libelle('TRANSFERT'));
        $this->assertSame('Bon de sortie', $entete->libelle('SORTIE'));
        $this->assertSame('N° TRF-001/CHU-YO/DG', $entete->reference('TRF-001'));
        $this->assertDatabaseHas('activity_log', ['description' => 'entete_documents_modifie']);

        $this->get(route('referentiel.parametres.entete.apercu'))->assertOk()
            ->assertSee('DIRECTION DES SERVICES PHARMACEUTIQUES')
            ->assertDontSee('SECRÉTARIAT GÉNÉRAL')
            ->assertSee('Bordereau de transfert')
            ->assertSee('03 BP 0000 Ouagadougou 03');
    }

    public function test_saisies_invalides_refusees(): void
    {
        $this->actingAs($this->utilisateur('admin'));

        $this->putJson(route('referentiel.parametres.entete.update'), ['entete_hierarchie' => []])
            ->assertStatus(422)->assertJsonValidationErrors('entete_hierarchie');
        $this->putJson(route('referentiel.parametres.entete.update'), ['entete_libelles' => ['INCONNU' => 'X']])
            ->assertStatus(422)->assertJsonValidationErrors('entete_libelles');
        $this->putJson(route('referentiel.parametres.entete.update'), ['entete_armoiries_position' => 'HAUT'])
            ->assertStatus(422)->assertJsonValidationErrors('entete_armoiries_position');
    }

    public function test_armoiries_integrees_puis_retirees(): void
    {
        Storage::fake('local');
        $this->capturerHtml();
        $this->actingAs($this->utilisateur('admin'));

        $this->post(route('referentiel.parametres.entete.image', 'entete_armoiries'), [
            'fichier' => UploadedFile::fake()->image('armoiries.png', 120, 120),
        ])->assertOk()->assertJsonPath('image', fn ($src) => str_starts_with($src, 'data:image/png;base64,'));

        $chemin = Parametres::get('entete_armoiries');
        Storage::disk('local')->assertExists($chemin);
        $this->assertDatabaseHas('activity_log', ['description' => 'entete_logo_modifie']);

        $this->get(route('referentiel.parametres.entete.apercu'))->assertSee('data:image/png;base64,', false);

        $this->deleteJson(route('referentiel.parametres.entete.image.supprimer', 'entete_armoiries'))->assertOk();
        Storage::disk('local')->assertMissing($chemin);
        $this->assertNull(Parametres::get('entete_armoiries'));
        $this->get(route('referentiel.parametres.entete.apercu'))->assertDontSee('data:image', false);
    }

    public function test_image_refusee_si_format_ou_cle_invalide(): void
    {
        Storage::fake('local');
        $this->actingAs($this->utilisateur('admin'));

        $this->postJson(route('referentiel.parametres.entete.image', 'entete_logo'), [
            'fichier' => UploadedFile::fake()->create('logo.gif', 20, 'image/gif'),
        ])->assertStatus(422)->assertJsonValidationErrors('fichier');

        $this->postJson(route('referentiel.parametres.entete.image', 'organisation_nom'), [
            'fichier' => UploadedFile::fake()->image('logo.png', 80, 80),
        ])->assertNotFound();
    }

    public function test_logo_disparu_du_disque_ne_bloque_pas_le_pdf(): void
    {
        Storage::fake('local');
        DB::table('parametres')->insert(['cle' => 'entete_logo', 'valeur' => 'entete/introuvable.png', 'created_at' => now(), 'updated_at' => now()]);

        $this->actingAs($this->utilisateur('admin'))
            ->get(route('referentiel.parametres.entete.apercu'))
            ->assertOk()->assertHeader('content-type', 'application/pdf');
    }

    public function test_reserve_a_l_administrateur(): void
    {
        $this->actingAs($this->utilisateur('gestionnaire'));

        $this->get(route('referentiel.parametres.entete'))->assertForbidden();
        $this->putJson(route('referentiel.parametres.entete.update'), ['entete_etat' => 'X'])->assertForbidden();
        $this->postJson(route('referentiel.parametres.entete.image', 'entete_logo'))->assertForbidden();
    }

    public function test_ecran_general_n_affiche_pas_l_en_tete(): void
    {
        $this->actingAs($this->utilisateur('admin'))
            ->get(route('referentiel.parametres.index'))
            ->assertOk()->assertDontSee('Devise nationale')->assertSee('En-tête des documents');

        // L'écran général ne modifie pas les paramètres de l'en-tête
        $this->putJson(route('referentiel.parametres.update'), ['entete_etat' => 'AUTRE'])->assertOk();
        $this->assertSame('BURKINA FASO', Parametres::get('entete_etat'));
    }
}
