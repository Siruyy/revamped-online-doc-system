<?php

namespace Tests\Feature\Exports;

use App\Models\ActivityLog;
use App\Models\DocumentRequest;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CsvExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_can_export_users_csv(): void
    {
        $superadmin = User::factory()->superadmin()->create();
        User::factory()->student()->create(['fullname' => 'Export Student', 'status' => 'active']);

        $response = $this->actingAs($superadmin)->get(route('superadmin.users.export'));

        $response->assertOk();
        $this->assertStringContainsString('Export Student', $response->streamedContent());
    }

    public function test_csv_exports_escape_formula_cells(): void
    {
        $superadmin = User::factory()->superadmin()->create();
        User::factory()->student()->create(['fullname' => '=HYPERLINK("https://evil.test")', 'status' => 'active']);
        User::factory()->student()->create(['fullname' => "\t=HYPERLINK(\"https://evil.test\")", 'status' => 'active']);
        User::factory()->student()->create(['fullname' => "\u{00A0}=HYPERLINK(\"https://evil.test\")", 'status' => 'active']);

        $response = $this->actingAs($superadmin)->get(route('superadmin.users.export'));

        $response->assertOk();
        $content = $response->streamedContent();
        $this->assertStringContainsString("'".'=HYPERLINK(""https://evil.test"")', $content);
        $this->assertStringContainsString("'\t".'=HYPERLINK(""https://evil.test"")', $content);
        $this->assertStringContainsString("'\u{00A0}".'=HYPERLINK(""https://evil.test"")', $content);
    }

    public function test_admin_can_export_requests_csv(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->student()->create(['fullname' => 'Request Export Student']);
        DocumentRequest::factory()->for($student)->create(['created_at' => now(), 'reference_no' => 'REQ-EXPORT-001']);

        $response = $this->actingAs($admin)->get(route('admin.reports.exports.requests'));

        $response->assertOk();
        $this->assertStringContainsString('REQ-EXPORT-001', $response->streamedContent());
    }

    public function test_admin_can_export_payments_csv(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->student()->create(['fullname' => 'Payment Export Student']);
        $request = DocumentRequest::factory()->for($student)->create(['reference_no' => 'REQ-PAY-001']);
        Payment::factory()->for($student)->for($request)->create(['created_at' => now(), 'total_amount' => 250]);

        $response = $this->actingAs($admin)->get(route('admin.reports.exports.payments'));

        $response->assertOk();
        $this->assertStringContainsString('REQ-PAY-001', $response->streamedContent());
    }

    public function test_superadmin_can_export_activity_logs_csv(): void
    {
        $superadmin = User::factory()->superadmin()->create();
        ActivityLog::factory()->create(['action' => 'export_test_action', 'description' => 'Export log row']);

        $response = $this->actingAs($superadmin)->get(route('superadmin.logs.export'));

        $response->assertOk();
        $this->assertStringContainsString('Export log row', $response->streamedContent());
    }

    public function test_student_cannot_export_admin_reports(): void
    {
        $student = User::factory()->student()->create();

        $this->actingAs($student)
            ->get(route('admin.reports.exports.requests'))
            ->assertForbidden();
    }
}
