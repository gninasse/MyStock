<?php

namespace Modules\Referentiel\Database\Seeders;

use Illuminate\Database\Seeder;

class ReferentielDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([RolesSeeder::class]);
    }
}
