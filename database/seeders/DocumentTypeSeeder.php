<?php

namespace Database\Seeders;

use App\Models\DocumentType;
use Illuminate\Database\Seeder;

class DocumentTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = config('policy.document_types', []);

        foreach ($types as $code => $spec) {
            DocumentType::updateOrCreate(
                ['code' => $code],
                [
                    'name' => $spec['name'],
                    'description' => $spec['name'].' — per registrar policy.',
                    'category' => $spec['category'],
                    'fee' => $spec['fee'],
                    'fee_formula' => 'per_page',
                    'default_page_count' => $spec['default_page_count'] ?? 1,
                    'processing_days' => $spec['sla_days'] ?? 3,
                    'submission_window' => $spec['submission_window'] ?? null,
                    'release_channel' => $spec['release_channel'] ?? null,
                    'offices' => $spec['offices'] ?? [],
                    'requirements' => $spec['requirements'] ?? [],
                    'flags' => $spec['flags'] ?? [],
                    'is_active' => true,
                ]
            );
        }
    }
}
