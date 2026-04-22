<?php

namespace Tests\Feature\Student;

use App\Models\Clearance;
use App\Models\DocumentRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ClearanceViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_view_clearance_page(): void
    {
        $student = $this->createActiveStudent();
        Clearance::factory()->for($student)->create();

        $this->actingAs($student)->get(route('student.clearance.show'))->assertOk();
    }

    public function test_student_can_submit_clearance_supporting_file(): void
    {
        Storage::fake('local');

        $student = $this->createActiveStudent();
        $clearance = Clearance::factory()->for($student)->create();

        $response = $this->actingAs($student)->post(route('student.clearance.submit'), [
            'clearance_file' => UploadedFile::fake()->create('clearance.pdf', 200),
        ]);

        $response->assertRedirect();
        $clearance->refresh();
        $this->assertNotNull($clearance->uploaded_file_path);
        Storage::disk('local')->assertExists($clearance->uploaded_file_path);
    }

    public function test_student_can_download_completed_clearance_pdf(): void
    {
        Storage::fake('local');

        $student = $this->createActiveStudent();
        $clearance = Clearance::factory()->for($student)->completed()->create([
            'pdf_path' => 'pdfs/clearance/'.$student->id.'.pdf',
        ]);

        Storage::disk('local')->put($clearance->pdf_path, 'pdf-content');

        $this->actingAs($student)->get(route('files.clearance-pdf', $clearance))->assertOk();
    }

    public function test_other_student_cannot_download_clearance_pdf(): void
    {
        Storage::fake('local');

        $owner = $this->createActiveStudent();
        $otherStudent = $this->createActiveStudent('other@student.local');
        $clearance = Clearance::factory()->for($owner)->completed()->create([
            'pdf_path' => 'pdfs/clearance/'.$owner->id.'.pdf',
        ]);
        Storage::disk('local')->put($clearance->pdf_path, 'pdf-content');

        $this->actingAs($otherStudent)->get(route('files.clearance-pdf', $clearance))->assertForbidden();
    }

    private function createActiveStudent(?string $email = null): User
    {
        return User::factory()->student()->create([
            'status' => 'active',
            'email_verified_at' => now(),
            'email' => $email ?? fake()->unique()->safeEmail(),
        ]);
    }
}
