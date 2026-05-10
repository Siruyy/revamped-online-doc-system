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
