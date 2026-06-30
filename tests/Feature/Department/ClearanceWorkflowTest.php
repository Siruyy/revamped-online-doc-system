<?php

namespace Tests\Feature\Department;

use App\Events\ClearanceUpdated;
use App\Models\Clearance;
use App\Models\DocumentRequest;
use App\Models\RequestRequirement;
use App\Models\User;
use App\Notifications\ClearanceCompletedNotification;
use App\Notifications\WorkflowStatusNotification;
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

    public function test_department_clearance_filters_include_public_request_snapshot_fields(): void
    {
        $teacher = $this->makeOfficer('teacher');
        $clearance = $this->makePublicClearance();

        $this->actingAs($teacher)
            ->get(route('department.clearances.index', [
                'status' => 'pending',
                'course' => 'BSIT',
                'year' => 3,
                'search' => 'PUBLIC-001',
            ]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Department/Clearances/Index')
                ->has('clearances.data', 1)
                ->where('clearances.data.0.id', $clearance->id)
                ->where('clearances.data.0.document_request.requester_name', 'Public Requestor')
                ->where('clearances.data.0.document_request.requester_student_id', 'PUBLIC-001'));
    }

    public function test_department_clearance_detail_includes_public_request_snapshot(): void
    {
        $teacher = $this->makeOfficer('teacher');
        $clearance = $this->makePublicClearance();
        $requirement = RequestRequirement::query()->create([
            'document_request_id' => $clearance->document_request_id,
            'requirement_key' => 'valid_id_photocopy_claimant',
            'label' => 'Valid ID photocopy',
            'status' => 'submitted',
            'file_path' => "request-requirements/public/{$clearance->document_request_id}/valid-id.pdf",
        ]);

        $this->actingAs($teacher)
            ->get(route('department.clearances.show', $clearance))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Department/Clearances/Show')
                ->where('clearance.user_id', null)
                ->where('clearance.document_request.requester_name', 'Public Requestor')
                ->where('clearance.document_request.requester_student_id', 'PUBLIC-001')
                ->where('clearance.document_request.requester_course', 'BSIT')
                ->where('clearance.document_request.requester_year_level', 3)
                ->where('clearance.document_request.requester_email', 'public-requestor@example.test')
                ->where('clearance.document_request.requirements.0.id', $requirement->id)
                ->where('clearance.document_request.requirements.0.label', 'Valid ID photocopy'));
    }

    public function test_teacher_can_sign_pending_clearance(): void
    {
        Event::fake([ClearanceUpdated::class]);
        Notification::fake();
        $teacher = $this->makeOfficer('teacher');
        $student = $this->makeStudent();
        $docRequest = DocumentRequest::factory()->for($student)->approved()->create();
        $clearance = Clearance::factory()->for($student)->for($docRequest)->create([
            'teacher_status' => 'pending',
            'dean_status' => 'pending',
            'accounting_status' => 'pending',
            'sao_status' => 'pending',
            'uploaded_file_path' => "clearance-files/{$student->id}/support.pdf",
        ]);

        $this->actingAs($teacher)->post(route('department.clearances.sign', $clearance), [
            'remarks' => 'Verified',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $clearance->refresh();
        $this->assertSame('cleared', $clearance->teacher_status);
        $this->assertSame($teacher->id, $clearance->teacher_signed_by);
        $this->assertSame('pending', $clearance->dean_status);
        $this->assertSame('pending', $clearance->accounting_status);
        $this->assertSame('pending', $clearance->sao_status);

        Event::assertDispatched(ClearanceUpdated::class, fn (ClearanceUpdated $event) => $event->clearanceId === $clearance->id
            && $event->studentId === $student->id
            && $event->department === 'teacher'
            && $event->action === 'signed'
            && $event->overallStatus === 'in_progress'
        );
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'clearance_signed',
            'user_id' => $teacher->id,
            'affected_user_id' => $student->id,
        ]);
    }

    public function test_each_department_role_can_sign_only_its_own_pending_column(): void
    {
        Event::fake([ClearanceUpdated::class]);
        Notification::fake();

        foreach (['teacher', 'dean', 'accounting', 'sao'] as $role) {
            $officer = $this->makeOfficer($role);
            $student = $this->makeStudent();
            $docRequest = DocumentRequest::factory()->for($student)->approved()->create();
            $clearance = Clearance::factory()->for($student)->for($docRequest)->create([
                'teacher_status' => 'pending',
                'dean_status' => 'pending',
                'accounting_status' => 'pending',
                'sao_status' => 'pending',
                'uploaded_file_path' => "clearance-files/{$student->id}/support.pdf",
            ]);

            $this->actingAs($officer)->post(route('department.clearances.sign', $clearance), [
                'remarks' => "{$role} verified",
            ])->assertRedirect()->assertSessionHasNoErrors();

            $clearance->refresh();
            $this->assertSame('cleared', $clearance->getAttribute("{$role}_status"));
            $this->assertSame("{$role} verified", $clearance->getAttribute("{$role}_remarks"));
            $this->assertSame($officer->id, $clearance->getAttribute("{$role}_signed_by"));
            $this->assertNotNull($clearance->getAttribute("{$role}_signed_at"));

            foreach (array_diff(['teacher', 'dean', 'accounting', 'sao'], [$role]) as $otherRole) {
                $this->assertSame('pending', $clearance->getAttribute("{$otherRole}_status"));
                $this->assertNull($clearance->getAttribute("{$otherRole}_signed_by"));
            }
        }
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

    public function test_teacher_cannot_sign_without_uploaded_supporting_file(): void
    {
        Event::fake([ClearanceUpdated::class]);

        $teacher = $this->makeOfficer('teacher');
        $student = $this->makeStudent();
        $docRequest = DocumentRequest::factory()->for($student)->approved()->create();
        $clearance = Clearance::factory()->for($student)->for($docRequest)->create([
            'teacher_status' => 'pending',
            'dean_status' => 'pending',
            'accounting_status' => 'pending',
            'sao_status' => 'pending',
            'uploaded_file_path' => null,
        ]);

        $this->actingAs($teacher)->post(route('department.clearances.sign', $clearance), [
            'remarks' => 'Verified',
        ])->assertRedirect()->assertSessionHasErrors([
            'sign' => 'Student must upload the clearance supporting file before department signing.',
        ]);

        $this->assertSame('pending', $clearance->refresh()->teacher_status);
    }

    public function test_dean_can_deny_with_remarks(): void
    {
        Event::fake([ClearanceUpdated::class]);
        Notification::fake();
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
        ])->assertRedirect()->assertSessionHasNoErrors();

        $clearance->refresh();
        $this->assertSame('denied', $clearance->dean_status);
        $this->assertStringContainsString('library', $clearance->dean_remarks ?? '');
        $this->assertSame('denied', $clearance->overall_status);

        Event::assertDispatched(ClearanceUpdated::class, fn (ClearanceUpdated $event) => $event->clearanceId === $clearance->id
            && $event->studentId === $student->id
            && $event->department === 'dean'
            && $event->action === 'denied'
            && $event->overallStatus === 'denied'
        );
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'clearance_denied',
            'user_id' => $dean->id,
            'affected_user_id' => $student->id,
        ]);
    }

    public function test_each_department_role_can_deny_only_its_own_pending_column(): void
    {
        Event::fake([ClearanceUpdated::class]);
        Notification::fake();

        foreach (['teacher', 'dean', 'accounting', 'sao'] as $role) {
            $officer = $this->makeOfficer($role);
            $student = $this->makeStudent();
            $docRequest = DocumentRequest::factory()->for($student)->approved()->create();
            $clearance = Clearance::factory()->for($student)->for($docRequest)->create([
                'teacher_status' => 'pending',
                'dean_status' => 'pending',
                'accounting_status' => 'pending',
                'sao_status' => 'pending',
            ]);

            $this->actingAs($officer)->post(route('department.clearances.deny', $clearance), [
                'remarks' => "{$role} requirement missing",
            ])->assertRedirect()->assertSessionHasNoErrors();

            $clearance->refresh();
            $this->assertSame('denied', $clearance->getAttribute("{$role}_status"));
            $this->assertSame("{$role} requirement missing", $clearance->getAttribute("{$role}_remarks"));
            $this->assertSame($officer->id, $clearance->getAttribute("{$role}_signed_by"));
            $this->assertSame('denied', $clearance->overall_status);

            foreach (array_diff(['teacher', 'dean', 'accounting', 'sao'], [$role]) as $otherRole) {
                $this->assertSame('pending', $clearance->getAttribute("{$otherRole}_status"));
                $this->assertNull($clearance->getAttribute("{$otherRole}_signed_by"));
            }
        }
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

    public function test_department_cannot_deny_when_clearance_is_not_in_progress(): void
    {
        $dean = $this->makeOfficer('dean');
        $student = $this->makeStudent();
        $docRequest = DocumentRequest::factory()->for($student)->approved()->create();
        $clearance = Clearance::factory()->for($student)->for($docRequest)->create([
            'dean_status' => 'pending',
            'overall_status' => 'completed',
        ]);

        $this->actingAs($dean)->post(route('department.clearances.deny', $clearance), [
            'remarks' => 'Missing library clearance paperwork',
        ])->assertForbidden();

        $this->assertSame('pending', $clearance->refresh()->dean_status);
    }

    public function test_department_cannot_deny_when_department_column_not_pending(): void
    {
        $dean = $this->makeOfficer('dean');
        $student = $this->makeStudent();
        $docRequest = DocumentRequest::factory()->for($student)->approved()->create();
        $clearance = Clearance::factory()->for($student)->for($docRequest)->create([
            'dean_status' => 'cleared',
            'dean_signed_by' => $dean->id,
            'dean_signed_at' => now(),
            'overall_status' => 'in_progress',
        ]);

        $this->actingAs($dean)->post(route('department.clearances.deny', $clearance), [
            'remarks' => 'Missing library clearance paperwork',
        ])->assertForbidden();

        $this->assertSame('cleared', $clearance->refresh()->dean_status);
    }

    public function test_all_departments_clearing_completes_clearance_and_generates_pdf_record(): void
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
            'uploaded_file_path' => "clearance-files/{$student->id}/support.pdf",
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

    public function test_each_department_role_can_sign_public_clearance_without_supporting_file(): void
    {
        Event::fake([ClearanceUpdated::class]);
        Notification::fake();

        foreach (['teacher', 'dean', 'accounting', 'sao'] as $role) {
            $officer = $this->makeOfficer($role);
            $clearance = $this->makePublicClearance();

            $this->actingAs($officer)->post(route('department.clearances.sign', $clearance), [
                'remarks' => "{$role} verified",
            ])->assertRedirect()->assertSessionHasNoErrors();

            $clearance->refresh();
            $this->assertSame('cleared', $clearance->getAttribute("{$role}_status"));
            $this->assertSame($officer->id, $clearance->getAttribute("{$role}_signed_by"));

            Event::assertDispatched(ClearanceUpdated::class, fn (ClearanceUpdated $event): bool => $event->clearanceId === $clearance->id
                && $event->studentId === null
                && $event->department === $role
                && $event->action === 'signed');
        }
    }

    public function test_each_department_role_can_deny_public_clearance_without_student_user(): void
    {
        Event::fake([ClearanceUpdated::class]);
        Notification::fake();

        foreach (['teacher', 'dean', 'accounting', 'sao'] as $role) {
            $officer = $this->makeOfficer($role);
            $clearance = $this->makePublicClearance();

            $this->actingAs($officer)->post(route('department.clearances.deny', $clearance), [
                'remarks' => "{$role} public requirement missing",
            ])->assertRedirect()->assertSessionHasNoErrors();

            $clearance->refresh();
            $this->assertSame('denied', $clearance->getAttribute("{$role}_status"));
            $this->assertSame('denied', $clearance->overall_status);
            $this->assertSame($officer->id, $clearance->getAttribute("{$role}_signed_by"));

            Event::assertDispatched(ClearanceUpdated::class, fn (ClearanceUpdated $event): bool => $event->clearanceId === $clearance->id
                && $event->studentId === null
                && $event->department === $role
                && $event->action === 'denied');
        }
    }

    public function test_public_clearance_completion_generates_pdf_and_emails_requestor(): void
    {
        Storage::fake('local');
        Event::fake();
        Notification::fake();

        $clearance = $this->makePublicClearance('public-requestor@example.test');

        foreach (['teacher', 'dean', 'accounting', 'sao'] as $role) {
            $this->actingAs($this->makeOfficer($role))
                ->post(route('department.clearances.sign', $clearance), [])
                ->assertRedirect()
                ->assertSessionHasNoErrors();
            $clearance->refresh();
        }

        $this->assertSame('completed', $clearance->overall_status);
        $this->assertSame("pdfs/clearance/public/{$clearance->document_request_id}/clearance-{$clearance->id}.pdf", $clearance->pdf_path);
        Storage::disk('local')->assertExists($clearance->pdf_path);

        Notification::assertSentOnDemand(
            WorkflowStatusNotification::class,
            fn (WorkflowStatusNotification $notification, array $channels, object $notifiable): bool => $channels === ['mail']
                && ($notifiable->routes['mail'] ?? null) === 'public-requestor@example.test'
                && ($notification->toArray($notifiable)['type'] ?? null) === 'clearance_completed',
        );
    }

    public function test_public_clearance_completion_skips_requestor_email_when_absent(): void
    {
        Storage::fake('local');
        Event::fake();
        Notification::fake();

        $clearance = $this->makePublicClearance(null);

        foreach (['teacher', 'dean', 'accounting', 'sao'] as $role) {
            $this->actingAs($this->makeOfficer($role))
                ->post(route('department.clearances.sign', $clearance), [])
                ->assertRedirect()
                ->assertSessionHasNoErrors();
            $clearance->refresh();
        }

        $this->assertSame('completed', $clearance->overall_status);
        Notification::assertSentOnDemandTimes(WorkflowStatusNotification::class, 0);
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

    private function makePublicClearance(?string $requesterEmail = 'public-requestor@example.test'): Clearance
    {
        $docRequest = DocumentRequest::factory()->approved()->create([
            'user_id' => null,
            'intake_mode' => 'public',
            'requester_name' => 'Public Requestor',
            'requester_email' => $requesterEmail,
            'requester_contact_number' => '09171234567',
            'requester_student_id' => 'PUBLIC-001',
            'requester_course' => 'BSIT',
            'requester_year_level' => 3,
        ]);

        return Clearance::factory()->for($docRequest, 'documentRequest')->create([
            'user_id' => null,
            'teacher_status' => 'pending',
            'dean_status' => 'pending',
            'accounting_status' => 'pending',
            'sao_status' => 'pending',
            'uploaded_file_path' => null,
        ]);
    }
}
