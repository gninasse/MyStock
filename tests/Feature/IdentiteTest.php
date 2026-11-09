<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Modules\Core\Models\User;
use Modules\Referentiel\Database\Seeders\RolesSeeder;
use Tests\TestCase;

class IdentiteTest extends TestCase
{
    use RefreshDatabase;

    public function test_les_fichiers_d_identite_existent(): void
    {
        foreach (['favicon.ico', 'favicon.svg', 'site.webmanifest', 'brand/logo.svg', 'brand/icon-32.png',
            'brand/apple-touch-icon.png', 'brand/icon-192.png', 'brand/icon-512.png', 'brand/icon-maskable-512.png'] as $fichier) {
            $this->assertFileExists(public_path($fichier));
        }
        $manifeste = json_decode(file_get_contents(public_path('site.webmanifest')), true);
        $this->assertSame('MyStock', $manifeste['short_name']);
    }

    public function test_la_connexion_porte_icones_titre_et_version(): void
    {
        $this->get(route('login'))->assertOk()
            ->assertSee('<title>Connexion · MyStock</title>', false)
            ->assertSee('site.webmanifest', false)
            ->assertSee('brand/apple-touch-icon.png', false)
            ->assertSee('v'.config('app.version'))
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_les_pages_ont_un_titre_d_onglet_et_le_logo(): void
    {
        $this->seed(RolesSeeder::class);
        $user = User::factory()->create()->assignRole('admin');

        $this->actingAs($user)->get(route('referentiel.parametres.entete'))->assertOk()
            ->assertSee('<title>En-tête des documents · MyStock</title>', false)
            ->assertSee('brand/logo.svg', false)
            ->assertSee('&copy; '.now()->year, false);
    }

    public function test_les_pages_d_erreur_sont_en_francais(): void
    {
        $this->get('/page-qui-n-existe-pas')->assertNotFound()->assertSee('Page introuvable');

        Route::middleware('web')->get('/_test/refus', fn () => abort(403, 'Seul un super-administrateur peut le faire.'));
        $this->get('/_test/refus')->assertForbidden()->assertSee('Accès refusé')->assertSee('Seul un super-administrateur peut le faire.');

        Route::middleware('web')->get('/_test/panne', fn () => abort(500));
        $this->get('/_test/panne')->assertStatus(500)->assertSee('Erreur interne');
    }
}
