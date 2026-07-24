<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ProductionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            DocumentTypeSeeder::class,
            AcademicProgramSeeder::class,
            SuperAdminSeeder::class,
            ClearanceSignatorySeeder::class,
        ]);
    }
}
