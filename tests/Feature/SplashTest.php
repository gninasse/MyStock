<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Core\Models\User;
use Modules\Referentiel\Database\Seeders\RolesSeeder;
use Tests\TestCase;

class SplashTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_page_de_connexion_affiche_l_ecran_de_demarrage(): void
    {
        $this->get(route('login'))->assertOk()->assertSee('id="splash"', false)->assertSee('CHU-YO');
    }

    public function test_les_pages_de_l_application_affichent_l_ecran_de_demarrage(): void
    {
        $this->seed(RolesSeeder::class);
        $user = User::factory()->create()->assignRole('admin');

        $this->actingAs($user)->get(route('referentiel.parametres.entete'))
            ->assertOk()->assertSee('id="splash"', false);
    }
}
