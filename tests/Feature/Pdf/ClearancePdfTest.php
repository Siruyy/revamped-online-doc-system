<?php

namespace Tests\Feature\Pdf;

use App\Models\Clearance;
use App\Models\User;
use App\Services\PdfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ClearancePdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_clearance_completion_generates_private_pdf(): void
    {
        Storage::fake('local');
        $student = User::factory()->student()->create();
        $clearance = Clearance::factory()->for($student)->completed()->create();

        $path = app(PdfService::class)->generateClearancePdf($clearance);

        $this->assertSame($path, $clearance->fresh()->pdf_path);
        $this->assertStringStartsWith('pdfs/clearance/'.$student->id.'/', $path);
        Storage::disk('local')->assertExists($path);
    }

    public function test_clearance_pdf_view_includes_student_and_signer_labels_without_private_paths(): void
    {
        $student = User::factory()->student()->create([
            'fullname' => 'PDF Content Student',
        ]);
        $teacher = User::factory()->teacher()->create([
            'fullname' => 'Teacher Signatory',
            'signature_path' => 'signatures/1/private-signature.png',
        ]);
        $dean = User::factory()->dean()->create(['fullname' => 'Dean Signatory']);
        $accounting = User::factory()->accounting()->create(['fullname' => 'Accounting Signatory']);
        $sao = User::factory()->sao()->create(['fullname' => 'SAO Signatory']);
        $clearance = Clearance::factory()->for($student)->completed()->create([
            'teacher_signed_by' => $teacher->id,
            'dean_signed_by' => $dean->id,
            'accounting_signed_by' => $accounting->id,
            'sao_signed_by' => $sao->id,
        ]);

        $html = view('pdf.clearance', [
            'clearance' => $clearance->loadMissing([
                'user',
                'documentRequest',
                'teacherSigner',
                'deanSigner',
                'accountingSigner',
                'saoSigner',
            ]),
            'generatedAt' => now(),
            'signatureImages' => [
                'teacher' => 'data:image/png;base64,signature-bytes',
            ],
        ])->render();

        $this->assertStringContainsString('PDF Content Student', $html);
        $this->assertStringContainsString('data:image/png;base64,signature-bytes', $html);
        $this->assertStringContainsString('Teacher Signer', $html);
        $this->assertStringContainsString('Dean Signer', $html);
        $this->assertStringContainsString('Accounting Signer', $html);
        $this->assertStringContainsString('SAO Signer', $html);
        $this->assertStringNotContainsString('signatures/1/private-signature.png', $html);
    }

    public function test_pdf_service_embeds_only_safe_private_signatures(): void
    {
        Storage::fake('local');
        $student = User::factory()->student()->create();
        $teacher = User::factory()->teacher()->create(['signature_path' => 'signatures/pending/signature.png']);
        $teacher->forceFill(['signature_path' => "signatures/{$teacher->id}/signature.png"])->save();
        $dean = User::factory()->dean()->create(['signature_path' => 'signatures/999/signature.png']);
        $clearance = Clearance::factory()->for($student)->completed()->create([
            'teacher_signed_by' => $teacher->id,
            'dean_signed_by' => $dean->id,
        ])->loadMissing(['teacherSigner', 'deanSigner']);

        Storage::disk('local')->put($teacher->signature_path, base64_decode('iVBORw0KGgo='));
        Storage::disk('local')->put($dean->signature_path, base64_decode('iVBORw0KGgo='));

        $method = new \ReflectionMethod(PdfService::class, 'signatureImages');
        $images = $method->invoke(app(PdfService::class), $clearance);

        $this->assertArrayHasKey('teacher', $images);
        $this->assertStringStartsWith('data:', $images['teacher']);
        $this->assertArrayNotHasKey('dean', $images);
    }

    public function test_clearance_pdf_download_requires_owner_scoped_path(): void
    {
        Storage::fake('local');
        $student = User::factory()->student()->create();
        $clearance = Clearance::factory()->for($student)->completed()->create([
            'pdf_path' => 'pdfs/clearance/999/clearance-1.pdf',
        ]);
        Storage::disk('local')->put($clearance->pdf_path, '%PDF-1.4 test');

        $this->actingAs($student)
            ->get(route('files.clearance-pdf', $clearance))
            ->assertNotFound();
    }

    public function test_incomplete_clearance_pdf_cannot_be_downloaded(): void
    {
        Storage::fake('local');
        $student = User::factory()->student()->create();
        $clearance = Clearance::factory()->for($student)->create([
            'overall_status' => 'in_progress',
            'pdf_path' => 'pdfs/clearance/'.$student->id.'/clearance-1.pdf',
        ]);
        Storage::disk('local')->put($clearance->pdf_path, '%PDF-1.4 test');

        $this->actingAs($student)
            ->get(route('files.clearance-pdf', $clearance))
            ->assertForbidden();
    }

    public function test_student_can_download_own_clearance_pdf(): void
    {
        Storage::fake('local');
        $student = User::factory()->student()->create();
        $clearance = Clearance::factory()->for($student)->completed()->create([
            'pdf_path' => 'pdfs/clearance/'.$student->id.'/clearance-1.pdf',
        ]);
        Storage::disk('local')->put($clearance->pdf_path, '%PDF-1.4 test');

        $this->actingAs($student)
            ->get(route('files.clearance-pdf', $clearance))
            ->assertOk();
    }

    public function test_student_cannot_download_another_students_clearance_pdf(): void
    {
        Storage::fake('local');
        $owner = User::factory()->student()->create();
        $other = User::factory()->student()->create();
        $clearance = Clearance::factory()->for($owner)->completed()->create([
            'pdf_path' => 'pdfs/clearance/'.$owner->id.'/clearance-1.pdf',
        ]);
        Storage::disk('local')->put($clearance->pdf_path, '%PDF-1.4 test');

        $this->actingAs($other)
            ->get(route('files.clearance-pdf', $clearance))
            ->assertForbidden();
    }
}
