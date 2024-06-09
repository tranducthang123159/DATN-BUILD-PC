<?php

namespace Modules\Brand\Database\Seeders;

use Illuminate\Database\Seeder;

class BrandDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call(BrandSeeder::class);
    }
}
