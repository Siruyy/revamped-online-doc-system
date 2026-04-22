<?php

namespace Database\Seeders;

use App\Models\DocumentType;
use Illuminate\Database\Seeder;

class DocumentTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            ['name' => 'Transcript of Records', 'category' => 'Academic', 'fee' => 250, 'processing_days' => 5],
            ['name' => 'Good Moral Certificate', 'category' => 'Records', 'fee' => 100, 'processing_days' => 3],
            ['name' => 'Certificate of Enrollment', 'category' => 'Academic', 'fee' => 120, 'processing_days' => 2],
            ['name' => 'Diploma', 'category' => 'Academic', 'fee' => 500, 'processing_days' => 10],
            ['name' => 'Honorable Dismissal', 'category' => 'Records', 'fee' => 150, 'processing_days' => 4],
        ];

        foreach ($types as $type) {
            DocumentType::updateOrCreate(
                ['name' => $type['name']],
                [
                    'description' => "{$type['name']} request document type",
                    'category' => $type['category'],
                    'fee' => $type['fee'],
                    'processing_days' => $type['processing_days'],
                    'is_active' => true,
                ]
            );
        }
    }
}
