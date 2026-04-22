<?php

namespace Tests\Unit\Policies;

use App\Models\Clearance;
use App\Models\DocumentRequest;
use App\Models\Payment;
use App\Models\User;
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
        $payment = Payment::factory()->for($student)->pending()->create();
        $policy = new PaymentPolicy;

        $this->assertTrue($policy->view($student, $payment));
        $this->assertTrue($policy->upload($student, $payment));
        $this->assertTrue($policy->approve($admin, $payment));
        $this->assertFalse($policy->deny($student, $payment));
    }

    public function test_clearance_policy_department_scope_permissions(): void
    {
        $teacher = User::factory()->teacher()->create();
        $dean = User::factory()->dean()->create();
        $student = User::factory()->student()->create();
        $clearance = Clearance::factory()->for($student)->create();
        $policy = new ClearancePolicy;

        $this->assertTrue($policy->view($teacher, $clearance));
        $this->assertTrue($policy->sign($teacher, $clearance));
        $this->assertTrue($policy->signFor($teacher, $clearance, 'teacher'));
        $this->assertFalse($policy->signFor($teacher, $clearance, 'dean'));
        $this->assertTrue($policy->sign($dean, $clearance));
        $this->assertTrue($policy->downloadPdf($student, $clearance));

        $clearedTeacher = Clearance::factory()->for($student)->create([
            'teacher_status' => 'cleared',
            'dean_status' => 'pending',
            'accounting_status' => 'pending',
            'sao_status' => 'pending',
        ]);
        $this->assertFalse($policy->sign($teacher, $clearedTeacher));

        $deniedTeacher = Clearance::factory()->for($student)->create([
            'teacher_status' => 'denied',
            'dean_status' => 'pending',
            'accounting_status' => 'pending',
            'sao_status' => 'pending',
        ]);
        $this->assertFalse($policy->sign($teacher, $deniedTeacher));
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
    }
}
