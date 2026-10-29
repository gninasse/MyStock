<?php

namespace Modules\Referentiel\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Core\Models\User;
use Modules\Referentiel\Database\Seeders\RolesSeeder;
use Tests\TestCase;

abstract class ReferentielTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
    }

    protected function utilisateur(string $role): User
    {
        return User::factory()->create()->assignRole($role);
    }
}
