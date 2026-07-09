<?php

namespace Tests\Unit\Policies;

use App\Models\ActivityLog;
use App\Models\Clearance;
use App\Models\DocumentRequest;
use App\Models\Payment;
use App\Models\User;
use App\Policies\ActivityLogPolicy;
use App\Policies\ClearancePolicy;
use App\Policies\DocumentRequestPolicy;
use App\Policies\PaymentPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_document_request_policy_permissions(): void
    {
        $student = User::factory()->student()->create();
        $admin = User::factory()->admin()->create();
        $otherStudent = User::factory()->student()->create();
        $request = DocumentRequest::factory()->for($student)->pending()->create();
        $policy = new DocumentRequestPolicy;

        $this->assertTrue($policy->view($student, $request));
        $this->assertFalse($policy->view($otherStudent, $request));
        $this->assertTrue($policy->approve($admin, $request));
        $this->assertTrue($policy->cancel($student, $request));
        $this->assertFalse($policy->delete($admin, $request));
    }

    public function test_payment_policy_permissions(): void
    {
        $student = User::factory()->student()->create();
        $admin = User::factory()->admin()->create();
        // Policy-initial: upload is only allowed once the document request is approved.
        $approvedRequest = DocumentRequest::factory()->for($student)->approved()->create();
        $payment = Payment::factory()->for($student)->for($approvedRequest)->pending()->create();
        $policy = new PaymentPolicy;

        $this->assertTrue($policy->view($student, $payment));
        $this->assertTrue($policy->upload($student, $payment));
        $this->assertTrue($policy->approve($admin, $payment));
        $this->assertFalse($policy->deny($student, $payment));

        // Upload is blocked when the linked request is still pending.
        $pendingRequest = DocumentRequest::factory()->for($student)->pending()->create();
        $blockedPayment = Payment::factory()->for($student)->for($pendingRequest)->pending()->create();
        $this->assertFalse($policy->upload($student, $blockedPayment));
    }

    public function test_clearance_policy_department_scope_permissions(): void
    {
        $president = User::factory()->signatory('president')->create();
        $dean = User::factory()->dean()->create();
        $student = User::factory()->student()->create();
        $clearance = Clearance::factory()->for($student)->create();
        $policy = new ClearancePolicy;

        $this->assertTrue($policy->view($president, $clearance));
        $this->assertTrue($policy->sign($president, $clearance));
        $this->assertTrue($policy->signFor($president, $clearance, 'president'));
        $this->assertFalse($policy->signFor($president, $clearance, 'dean'));
        $this->assertTrue($policy->sign($dean, $clearance));
        $this->assertFalse($policy->downloadPdf($student, $clearance));

        $completedClearance = Clearance::factory()->completed()->for($student)->create();
        $this->assertTrue($policy->downloadPdf($student, $completedClearance));

        $clearedPresident = Clearance::factory()->for($student)->create([
            'president_status' => 'cleared',
        ]);
        $this->assertFalse($policy->sign($president, $clearedPresident));

        $deniedPresident = Clearance::factory()->for($student)->create([
            'president_status' => 'denied',
        ]);
        $this->assertFalse($policy->sign($president, $deniedPresident));

        $completedPresident = Clearance::factory()->for($student)->create([
            'president_status' => 'pending',
            'overall_status' => 'completed',
        ]);
        $this->assertFalse($policy->sign($president, $completedPresident));
        $this->assertTrue($policy->rejectDepartment($dean, $clearance));
        $this->assertFalse($policy->rejectDepartment($president, $completedPresident));
    }

    public function test_clearance_policy_department_view_requires_relevant_workflow_record(): void
    {
        $president = User::factory()->signatory('president')->create();
        $student = User::factory()->student()->create();
        $policy = new ClearancePolicy;

        $relevantClearance = Clearance::factory()->for($student)->make([
            'president_status' => 'pending',
            'overall_status' => 'in_progress',
        ]);
        $this->assertTrue($policy->view($president, $relevantClearance));

        $missingPresidentStatus = Clearance::factory()->for($student)->make([
            'president_status' => null,
            'overall_status' => 'in_progress',
        ]);
        $this->assertFalse($policy->view($president, $missingPresidentStatus));

        $outsideWorkflow = Clearance::factory()->for($student)->make([
            'president_status' => 'pending',
            'overall_status' => 'not_started',
        ]);
        $this->assertFalse($policy->view($president, $outsideWorkflow));
    }

    public function test_user_policy_superadmin_controls(): void
    {
        $superAdmin = User::factory()->superadmin()->create();
        $pendingStudent = User::factory()->pending()->create();
        $policy = new UserPolicy;

        $this->assertTrue($policy->viewAny($superAdmin));
        $this->assertTrue($policy->approve($superAdmin, $pendingStudent));
        $this->assertTrue($policy->reject($superAdmin, $pendingStudent));
        $this->assertTrue($policy->delete($superAdmin, $pendingStudent));
        $this->assertFalse($policy->delete($superAdmin, $superAdmin));

        $suspended = User::factory()->admin()->create(['status' => 'suspended']);
        $this->assertTrue($policy->reactivate($superAdmin, $suspended));
        $this->assertFalse($policy->reactivate($superAdmin, $pendingStudent));
    }

    public function test_activity_log_policy_superadmin_only(): void
    {
        $superAdmin = User::factory()->superadmin()->create();
        $admin = User::factory()->admin()->create();
        $log = new ActivityLog(['action' => 'x', 'description' => 'y']);
        $policy = new ActivityLogPolicy;

        $this->assertTrue($policy->viewAny($superAdmin));
        $this->assertTrue($policy->view($superAdmin, $log));
        $this->assertFalse($policy->viewAny($admin));
    }
}
