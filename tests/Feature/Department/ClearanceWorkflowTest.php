<?php

namespace Tests\Feature\Department;

use App\Events\ClearanceUpdated;
use App\Models\Clearance;
use App\Models\DocumentRequest;
use App\Models\RequestRequirement;
use App\Models\User;
use App\Notifications\WorkflowStatusNotification;
use App\Support\ClearanceSignatories;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ClearanceWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_signatory_can_list_and_view_clearances(): void
    {
        $officer = $this->makeOfficer('dean');
        $student = $this->makeStudent();
        $request = DocumentRequest::factory()->for($student)->approved()->create();
        $clearance = Clearance::factory()->for($student)->for($request)->create();

        $this->actingAs($officer)->get(route('department.clearances.index'))->assertOk();
        $this->actingAs($officer)->get(route('department.clearances.show', $clearance))->assertOk();
    }

    public function test_student_cannot_access_department_clearance_routes(): void
    {
        $student = $this->makeStudent();
        $clearance = Clearance::factory()->for($student)->create();

        $this->actingAs($student)->get(route('department.clearances.index'))->assertForbidden();
        $this->actingAs($student)->get(route('department.clearances.show', $clearance))->assertForbidden();
    }

    public function test_signatory_clearance_filters_include_public_request_snapshot_fields(): void
    {
        $officer = $this->makeOfficer('president');
        $clearance = $this->makePublicClearance();

        $this->actingAs($officer)
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
                ->where('clearances.data.0.document_request.requester_student_id', 'PUBLIC-001')
                ->where('departmentStatusColumn', 'president_status')
                ->has('signatories', 6));
    }

    public function test_signatory_clearance_detail_includes_public_request_snapshot_and_requirements(): void
    {
        $officer = $this->makeOfficer('librarian');
        $clearance = $this->makePublicClearance();
        $requirement = RequestRequirement::query()->create([
            'document_request_id' => $clearance->document_request_id,
            'requirement_key' => 'valid_id_photocopy_claimant',
            'label' => 'Valid ID photocopy',
            'status' => 'submitted',
            'file_path' => "request-requirements/public/{$clearance->document_request_id}/valid-id.pdf",
        ]);

        $this->actingAs($officer)
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
                ->where('clearance.document_request.requirements.0.label', 'Valid ID photocopy')
                ->where('currentSignatory.label', 'Librarian')
                ->has('signatories', 6));
    }

    public function test_each_required_signatory_can_sign_only_its_own_pending_column(): void
    {
        Event::fake([ClearanceUpdated::class]);
        Notification::fake();

        foreach (ClearanceSignatories::roles() as $role) {
            $officer = $this->makeOfficer($role);
            $student = $this->makeStudent();
            $request = DocumentRequest::factory()->for($student)->approved()->create();
            $clearance = Clearance::factory()->for($student)->for($request)->create([
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

            foreach (array_diff(ClearanceSignatories::roles(), [$role]) as $otherRole) {
                $this->assertSame('pending', $clearance->getAttribute("{$otherRole}_status"));
                $this->assertNull($clearance->getAttribute("{$otherRole}_signed_by"));
            }
        }
    }

    public function test_signatory_cannot_sign_when_own_column_not_pending(): void
    {
        $officer = $this->makeOfficer('dean');
        $student = $this->makeStudent();
        $request = DocumentRequest::factory()->for($student)->approved()->create();
        $clearance = Clearance::factory()->for($student)->for($request)->create([
            'dean_status' => 'cleared',
            'dean_signed_by' => $officer->id,
            'dean_signed_at' => now(),
        ]);

        $this->actingAs($officer)->post(route('department.clearances.sign', $clearance), [
            'remarks' => 'Again',
        ])->assertForbidden();
    }

    public function test_signatory_cannot_sign_student_clearance_without_uploaded_supporting_file(): void
    {
        Event::fake([ClearanceUpdated::class]);

        $officer = $this->makeOfficer('dean');
        $student = $this->makeStudent();
        $request = DocumentRequest::factory()->for($student)->approved()->create();
        $clearance = Clearance::factory()->for($student)->for($request)->create([
            'uploaded_file_path' => null,
        ]);

        $this->actingAs($officer)->post(route('department.clearances.sign', $clearance), [
            'remarks' => 'Verified',
        ])->assertRedirect()->assertSessionHasErrors([
            'sign' => 'Student must upload the clearance supporting file before department signing.',
        ]);

        $this->assertSame('pending', $clearance->refresh()->dean_status);
    }

    public function test_each_required_signatory_can_deny_its_own_pending_column(): void
    {
        Event::fake([ClearanceUpdated::class]);
        Notification::fake();

        foreach (ClearanceSignatories::roles() as $role) {
            $officer = $this->makeOfficer($role);
            $student = $this->makeStudent();
            $request = DocumentRequest::factory()->for($student)->approved()->create();
            $clearance = Clearance::factory()->for($student)->for($request)->create();

            $this->actingAs($officer)->post(route('department.clearances.deny', $clearance), [
                'remarks' => "{$role} requirement missing",
            ])->assertRedirect()->assertSessionHasNoErrors();

            $clearance->refresh();
            $this->assertSame('denied', $clearance->getAttribute("{$role}_status"));
            $this->assertSame('denied', $clearance->overall_status);
            $this->assertSame($officer->id, $clearance->getAttribute("{$role}_signed_by"));
        }
    }

    public function test_public_clearance_completion_generates_pdf_and_emails_requestor(): void
    {
        Storage::fake('local');
        Event::fake();
        Notification::fake();

        $clearance = $this->makePublicClearance('public-requestor@example.test');

        foreach (ClearanceSignatories::roles() as $role) {
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

    public function test_department_can_download_supporting_file(): void
    {
        Storage::fake('local');

        $officer = $this->makeOfficer('dean');
        $student = $this->makeStudent();
        $request = DocumentRequest::factory()->for($student)->approved()->create();
        $path = "clearance-files/{$student->id}/support.pdf";
        Storage::disk('local')->put($path, 'binary');
        $clearance = Clearance::factory()->for($student)->for($request)->create([
            'uploaded_file_path' => $path,
        ]);

        $this->actingAs($officer)->get(route('files.clearance-supporting', $clearance))->assertOk();
    }

    public function test_department_dashboard_and_faq_load(): void
    {
        $officer = $this->makeOfficer('dean');

        $this->actingAs($officer)->get(route('department.dashboard'))->assertOk();
        $this->actingAs($officer)->get(route('department.faq.index'))->assertOk();
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
        $factory = ClearanceSignatories::isSignatoryRole($role)
            ? User::factory()->signatory($role)
            : User::factory()->{$role}();

        return $factory->create([
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
    }

    private function makePublicClearance(?string $requesterEmail = 'public-requestor@example.test'): Clearance
    {
        $request = DocumentRequest::factory()->approved()->create([
            'user_id' => null,
            'intake_mode' => 'public',
            'requester_name' => 'Public Requestor',
            'requester_email' => $requesterEmail,
            'requester_contact_number' => '09171234567',
            'requester_student_id' => 'PUBLIC-001',
            'requester_course' => 'BSIT',
            'requester_year_level' => 3,
        ]);

        return Clearance::factory()->for($request, 'documentRequest')->create([
            'user_id' => null,
            'uploaded_file_path' => null,
        ]);
    }
}
