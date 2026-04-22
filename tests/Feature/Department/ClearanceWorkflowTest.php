<?php

namespace Tests\Feature\Department;

use App\Models\Clearance;
use App\Models\DocumentRequest;
use App\Models\User;
use App\Notifications\ClearanceCompletedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ClearanceWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_department_officer_can_list_and_view_clearances(): void
    {
        $teacher = $this->makeOfficer('teacher');
        $student = $this->makeStudent();
        $docRequest = DocumentRequest::factory()->for($student)->approved()->create();
        $clearance = Clearance::factory()->for($student)->for($docRequest)->create();

        $this->actingAs($teacher)->get(route('department.clearances.index'))->assertOk();
        $this->actingAs($teacher)->get(route('department.clearances.show', $clearance))->assertOk();
    }

    public function test_student_cannot_access_department_clearance_routes(): void
    {
        $student = $this->makeStudent();
        $clearance = Clearance::factory()->for($student)->create();

        $this->actingAs($student)->get(route('department.clearances.index'))->assertForbidden();
        $this->actingAs($student)->get(route('department.clearances.show', $clearance))->assertForbidden();
    }

    public function test_teacher_can_sign_pending_clearance(): void
    {
        Event::fake();
        $teacher = $this->makeOfficer('teacher');
        $student = $this->makeStudent();
        $docRequest = DocumentRequest::factory()->for($student)->approved()->create();
        $clearance = Clearance::factory()->for($student)->for($docRequest)->create([
            'teacher_status' => 'pending',
            'dean_status' => 'pending',
            'accounting_status' => 'pending',
            'sao_status' => 'pending',
        ]);

        $this->actingAs($teacher)->post(route('department.clearances.sign', $clearance), [
            'remarks' => 'Verified',
        ])->assertRedirect();

        $clearance->refresh();
        $this->assertSame('cleared', $clearance->teacher_status);
        $this->assertSame($teacher->id, $clearance->teacher_signed_by);
    }

    public function test_teacher_cannot_sign_when_teacher_column_not_pending(): void
    {
        $teacher = $this->makeOfficer('teacher');
        $student = $this->makeStudent();
        $docRequest = DocumentRequest::factory()->for($student)->approved()->create();
        $clearance = Clearance::factory()->for($student)->for($docRequest)->create([
            'teacher_status' => 'cleared',
            'teacher_signed_by' => $teacher->id,
            'teacher_signed_at' => now(),
            'dean_status' => 'pending',
            'accounting_status' => 'pending',
            'sao_status' => 'pending',
        ]);

        $this->actingAs($teacher)->post(route('department.clearances.sign', $clearance), [
            'remarks' => 'Again',
        ])->assertForbidden();
    }

    public function test_dean_can_deny_with_remarks(): void
    {
        Event::fake();
        $dean = $this->makeOfficer('dean');
        $student = $this->makeStudent();
        $docRequest = DocumentRequest::factory()->for($student)->approved()->create();
        $clearance = Clearance::factory()->for($student)->for($docRequest)->create([
            'teacher_status' => 'cleared',
            'dean_status' => 'pending',
            'accounting_status' => 'pending',
            'sao_status' => 'pending',
        ]);

        $this->actingAs($dean)->post(route('department.clearances.deny', $clearance), [
            'remarks' => 'Missing library clearance paperwork',
        ])->assertRedirect();

        $clearance->refresh();
        $this->assertSame('denied', $clearance->dean_status);
        $this->assertStringContainsString('library', $clearance->dean_remarks ?? '');
        $this->assertSame('denied', $clearance->overall_status);
    }

    public function test_deny_requires_minimum_remarks_length(): void
    {
        $dean = $this->makeOfficer('dean');
        $student = $this->makeStudent();
        $docRequest = DocumentRequest::factory()->for($student)->approved()->create();
        $clearance = Clearance::factory()->for($student)->for($docRequest)->create([
            'teacher_status' => 'cleared',
            'dean_status' => 'pending',
            'accounting_status' => 'pending',
            'sao_status' => 'pending',
        ]);

        $this->actingAs($dean)->post(route('department.clearances.deny', $clearance), [
            'remarks' => 'short',
        ])->assertSessionHasErrors('remarks');
    }

    public function test_all_departments_clearing_completes_clearance_and_stubs_pdf(): void
    {
        Event::fake();
        Notification::fake();

        $student = $this->makeStudent();
        $docRequest = DocumentRequest::factory()->for($student)->approved()->create();
        $clearance = Clearance::factory()->for($student)->for($docRequest)->create([
            'teacher_status' => 'pending',
            'dean_status' => 'pending',
            'accounting_status' => 'pending',
            'sao_status' => 'pending',
        ]);

        $this->actingAs($this->makeOfficer('teacher'))->post(route('department.clearances.sign', $clearance), [])->assertRedirect();
        $clearance->refresh();
        $this->actingAs($this->makeOfficer('dean'))->post(route('department.clearances.sign', $clearance), [])->assertRedirect();
        $clearance->refresh();
        $this->actingAs($this->makeOfficer('accounting'))->post(route('department.clearances.sign', $clearance), [])->assertRedirect();
        $clearance->refresh();
        $this->actingAs($this->makeOfficer('sao'))->post(route('department.clearances.sign', $clearance), [])->assertRedirect();

        $clearance->refresh();
        $this->assertSame('completed', $clearance->overall_status);
        $this->assertNotNull($clearance->pdf_path);
        $this->assertStringStartsWith('pdfs/clearance/', $clearance->pdf_path);

        Notification::assertSentTo($student, ClearanceCompletedNotification::class);
    }

    public function test_department_can_download_supporting_file(): void
    {
        Storage::fake('local');

        $teacher = $this->makeOfficer('teacher');
        $student = $this->makeStudent();
        $docRequest = DocumentRequest::factory()->for($student)->approved()->create();
        $path = "clearance-files/{$student->id}/support.pdf";
        Storage::disk('local')->put($path, 'binary');
        $clearance = Clearance::factory()->for($student)->for($docRequest)->create([
            'uploaded_file_path' => $path,
            'teacher_status' => 'pending',
            'dean_status' => 'pending',
            'accounting_status' => 'pending',
            'sao_status' => 'pending',
        ]);

        $this->actingAs($teacher)->get(route('files.clearance-supporting', $clearance))->assertOk();
    }

    public function test_department_dashboard_loads(): void
    {
        $teacher = $this->makeOfficer('teacher');
        $this->actingAs($teacher)->get(route('department.dashboard'))->assertOk();
    }

    public function test_department_faq_page_loads(): void
    {
        $teacher = $this->makeOfficer('teacher');
        $this->actingAs($teacher)->get(route('department.faq.index'))->assertOk();
    }

    private function makeStudent(): User
    {
        return User::factory()->student()->create([
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
    }

    private function makeOfficer(string $role): User
    {
        return User::factory()->{$role}()->create([
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
    }
}
