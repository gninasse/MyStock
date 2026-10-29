<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Referentiel\Database\Seeders\ReferentielDatabaseSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Les utilisateurs se créent avec `php artisan cores:create-user`.
     */
    public function run(): void
    {
        $this->call([ReferentielDatabaseSeeder::class]);
    }
}
