<?php

namespace Tests\Unit\Models;

use App\Models\DocumentRequest;
use App\Models\DocumentType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentRequestModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_reference_number_is_auto_generated_on_create(): void
    {
        $student = User::factory()->student()->create();
        $documentType = DocumentType::factory()->create();

        $request = DocumentRequest::create([
            'user_id' => $student->id,
            'document_type_id' => $documentType->id,
            'status' => 'pending',
            'processing_stage' => 'not_started',
            'purpose' => 'For employment',
        ]);

        $this->assertNotNull($request->reference_no);
        $this->assertMatchesRegularExpression('/^REQ-\d{4}-\d{6}$/', $request->reference_no);
    }
}
