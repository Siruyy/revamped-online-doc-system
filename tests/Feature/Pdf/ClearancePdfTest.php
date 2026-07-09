<?php

namespace Tests\Feature\Pdf;

use App\Models\Clearance;
use App\Models\DocumentRequest;
use App\Models\User;
use App\Services\PdfService;
use App\Support\ClearanceSignatories;
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
        $dean = User::factory()->dean()->create(['fullname' => 'Dean Signatory']);
        $president = User::factory()->president()->create(['fullname' => 'President Signatory']);
        $librarian = User::factory()->librarian()->create(['fullname' => 'Librarian Signatory']);
        $studentAffairs = User::factory()->studentAffairs()->create(['fullname' => 'Student Affairs Signatory']);
        $alumni = User::factory()->alumni()->create(['fullname' => 'Alumni Signatory']);
        $guidance = User::factory()->guidance()->create(['fullname' => 'Guidance Signatory']);
        $clearance = Clearance::factory()->for($student)->completed()->create([
            'dean_signed_by' => $dean->id,
            'president_signed_by' => $president->id,
            'librarian_signed_by' => $librarian->id,
            'student_affairs_signed_by' => $studentAffairs->id,
            'alumni_signed_by' => $alumni->id,
            'guidance_signed_by' => $guidance->id,
        ]);

        $html = view('pdf.clearance', [
            'clearance' => $clearance->loadMissing([
                'user',
                'documentRequest',
                ...ClearanceSignatories::signerRelations(),
            ]),
            'generatedAt' => now(),
            'signatories' => ClearanceSignatories::definitions(),
        ])->render();

        $this->assertStringContainsString('PDF Content Student', $html);
        $this->assertStringContainsString('Dean', $html);
        $this->assertStringContainsString('Dean Signatory', $html);
        $this->assertStringContainsString('Office of the President', $html);
        $this->assertStringContainsString('President Signatory', $html);
        $this->assertStringContainsString('Librarian Signatory', $html);
        $this->assertStringContainsString('Student Affairs Signatory', $html);
        $this->assertStringContainsString('Alumni Signatory', $html);
        $this->assertStringContainsString('Guidance Signatory', $html);
        $this->assertStringContainsString('Date Signed', $html);
    }

    public function test_clearance_pdf_view_uses_public_request_snapshot_when_user_is_absent(): void
    {
        $request = DocumentRequest::factory()->create([
            'user_id' => null,
            'intake_mode' => 'public',
            'requester_name' => 'Public PDF Requestor',
            'requester_student_id' => 'PUBLIC-999',
            'requester_course' => 'BSIT',
            'requester_year_level' => 4,
        ]);
        $clearance = Clearance::factory()->for($request, 'documentRequest')->completed()->create([
            'user_id' => null,
        ]);

        $html = view('pdf.clearance', [
            'clearance' => $clearance->loadMissing([
                'user',
                'documentRequest',
                ...ClearanceSignatories::signerRelations(),
            ]),
            'generatedAt' => now(),
            'signatories' => ClearanceSignatories::definitions(),
        ])->render();

        $this->assertStringContainsString('Public PDF Requestor', $html);
        $this->assertStringContainsString('PUBLIC-999', $html);
        $this->assertStringContainsString('BSIT / 4', $html);
    }

    public function test_pdf_service_generates_public_clearance_pdf_under_public_request_path(): void
    {
        Storage::fake('local');
        $request = DocumentRequest::factory()->create([
            'user_id' => null,
            'intake_mode' => 'public',
            'requester_name' => 'Public PDF Requestor',
            'requester_student_id' => 'PUBLIC-999',
            'requester_course' => 'BSIT',
            'requester_year_level' => 4,
        ]);
        $clearance = Clearance::factory()->for($request, 'documentRequest')->completed()->create([
            'user_id' => null,
        ]);

        $path = app(PdfService::class)->generateClearancePdf($clearance);

        $this->assertSame("pdfs/clearance/public/{$request->id}/clearance-{$clearance->id}.pdf", $path);
        $this->assertSame($path, $clearance->fresh()->pdf_path);
        Storage::disk('local')->assertExists($path);
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

    public function test_admin_and_superadmin_can_download_public_clearance_pdf(): void
    {
        Storage::fake('local');
        $request = DocumentRequest::factory()->create(['user_id' => null, 'intake_mode' => 'public']);
        $clearance = Clearance::factory()->for($request, 'documentRequest')->completed()->create([
            'user_id' => null,
            'pdf_path' => "pdfs/clearance/public/{$request->id}/clearance-1.pdf",
        ]);
        Storage::disk('local')->put($clearance->pdf_path, '%PDF-1.4 test');

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('files.clearance-pdf', $clearance))
            ->assertOk();

        $this->actingAs(User::factory()->superadmin()->create())
            ->get(route('files.clearance-pdf', $clearance))
            ->assertOk();
    }

    public function test_student_and_department_cannot_download_public_clearance_pdf(): void
    {
        Storage::fake('local');
        $request = DocumentRequest::factory()->create(['user_id' => null, 'intake_mode' => 'public']);
        $clearance = Clearance::factory()->for($request, 'documentRequest')->completed()->create([
            'user_id' => null,
            'pdf_path' => "pdfs/clearance/public/{$request->id}/clearance-1.pdf",
        ]);
        Storage::disk('local')->put($clearance->pdf_path, '%PDF-1.4 test');

        $this->actingAs(User::factory()->student()->create())
            ->get(route('files.clearance-pdf', $clearance))
            ->assertForbidden();

        $this->actingAs(User::factory()->teacher()->create())
            ->get(route('files.clearance-pdf', $clearance))
            ->assertForbidden();
    }
}
