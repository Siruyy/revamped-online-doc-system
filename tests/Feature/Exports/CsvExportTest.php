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

    public function test_superadmin_users_export_has_exact_phase_nine_headings_and_approved_at(): void
    {
        $superadmin = User::factory()->superadmin()->create();
        $approvedAt = now()->setDate(2026, 5, 13)->setTime(9, 30);
        User::factory()->student()->create([
            'fullname' => 'Approved Export Student',
            'email' => 'approved-export@example.test',
            'approved_at' => $approvedAt,
        ]);

        $response = $this->actingAs($superadmin)->get(route('superadmin.users.export', [
            'search' => 'approved-export@example.test',
        ]));

        $response->assertOk();
        $rows = array_map('str_getcsv', explode("\n", trim($response->streamedContent())));

        $this->assertSame([
            'ID', 'Full Name', 'Email', 'Role', 'Status', 'Course', 'Year', 'Created At', 'Approved At',
        ], $rows[0]);
        $this->assertSame($approvedAt->toDateTimeString(), $rows[1][8]);
    }

    public function test_superadmin_users_export_preserves_filters(): void
    {
        $superadmin = User::factory()->superadmin()->create();
        User::factory()->student()->create([
            'fullname' => 'Filtered Export Student',
            'email' => 'filtered-export@example.test',
            'role' => 'student',
            'status' => 'active',
            'course' => 'BSIT',
            'year_level' => 2,
        ]);
        User::factory()->student()->create([
            'fullname' => 'Hidden Export Student',
            'email' => 'hidden-export@example.test',
            'role' => 'student',
            'status' => 'pending',
            'course' => 'BSCS',
            'year_level' => 1,
        ]);

        $response = $this->actingAs($superadmin)->get(route('superadmin.users.export', [
            'role' => 'student',
            'status' => 'active',
            'course' => 'BSIT',
            'year' => 2,
            'search' => 'filtered-export',
        ]));

        $response->assertOk();
        $content = $response->streamedContent();
        $this->assertStringContainsString('Filtered Export Student', $content);
        $this->assertStringNotContainsString('Hidden Export Student', $content);
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

    public function test_admin_requests_export_preserves_report_filters(): void
    {
        $admin = User::factory()->admin()->create();
        $includedStudent = User::factory()->student()->create(['course' => 'BSIT']);
        $excludedStudent = User::factory()->student()->create(['course' => 'BSCS']);
        DocumentRequest::factory()->for($includedStudent)->create([
            'created_at' => now()->setDate(2026, 1, 15),
            'reference_no' => 'REQ-FILTERED-001',
            'status' => 'completed',
        ]);
        DocumentRequest::factory()->for($excludedStudent)->create([
            'created_at' => now()->setDate(2026, 1, 15),
            'reference_no' => 'REQ-HIDDEN-001',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.reports.exports.requests', [
            'from' => '2026-01-01',
            'to' => '2026-01-31',
            'status' => 'completed',
            'course' => 'BSIT',
        ]));

        $response->assertOk();
        $content = $response->streamedContent();
        $this->assertStringContainsString('REQ-FILTERED-001', $content);
        $this->assertStringNotContainsString('REQ-HIDDEN-001', $content);
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

    public function test_admin_payments_export_preserves_report_filters(): void
    {
        $admin = User::factory()->admin()->create();
        $includedStudent = User::factory()->student()->create(['course' => 'BSIT']);
        $excludedStudent = User::factory()->student()->create(['course' => 'BSCS']);
        $includedRequest = DocumentRequest::factory()->for($includedStudent)->create(['reference_no' => 'REQ-PAY-FILTERED']);
        $excludedRequest = DocumentRequest::factory()->for($excludedStudent)->create(['reference_no' => 'REQ-PAY-HIDDEN']);
        Payment::factory()->for($includedStudent)->for($includedRequest)->create(['created_at' => now()->setDate(2026, 1, 15)]);
        Payment::factory()->for($excludedStudent)->for($excludedRequest)->create(['created_at' => now()->setDate(2026, 1, 15)]);

        $response = $this->actingAs($admin)->get(route('admin.reports.exports.payments', [
            'from' => '2026-01-01',
            'to' => '2026-01-31',
            'course' => 'BSIT',
        ]));

        $response->assertOk();
        $content = $response->streamedContent();
        $this->assertStringContainsString('REQ-PAY-FILTERED', $content);
        $this->assertStringNotContainsString('REQ-PAY-HIDDEN', $content);
    }

    public function test_superadmin_can_export_activity_logs_csv(): void
    {
        $superadmin = User::factory()->superadmin()->create();
        ActivityLog::factory()->create(['action' => 'export_test_action', 'description' => 'Export log row']);

        $response = $this->actingAs($superadmin)->get(route('superadmin.logs.export'));

        $response->assertOk();
        $this->assertStringContainsString('Export log row', $response->streamedContent());
    }

    public function test_non_superadmin_cannot_export_activity_logs_csv(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('superadmin.logs.export'))
            ->assertForbidden();
    }

    public function test_superadmin_activity_logs_export_preserves_filters(): void
    {
        $superadmin = User::factory()->superadmin()->create();
        $actor = User::factory()->admin()->create();
        $affected = User::factory()->student()->create();
        ActivityLog::factory()->create([
            'action' => 'filtered_action',
            'description' => 'Filtered log row',
            'user_id' => $actor->id,
            'affected_user_id' => $affected->id,
            'created_at' => now()->setDate(2026, 1, 15),
        ]);
        ActivityLog::factory()->create([
            'action' => 'hidden_action',
            'description' => 'Hidden log row',
            'created_at' => now()->setDate(2026, 1, 15),
        ]);

        $response = $this->actingAs($superadmin)->get(route('superadmin.logs.export', [
            'action' => 'filtered_action',
            'user_id' => $actor->id,
            'affected_user_id' => $affected->id,
            'from' => '2026-01-01',
            'to' => '2026-01-31',
            'q' => 'Filtered',
        ]));

        $response->assertOk();
        $content = $response->streamedContent();
        $this->assertStringContainsString('Filtered log row', $content);
        $this->assertStringNotContainsString('Hidden log row', $content);
    }

    public function test_student_cannot_export_admin_reports(): void
    {
        $student = User::factory()->student()->create();

        $this->actingAs($student)
            ->get(route('admin.reports.exports.requests'))
            ->assertForbidden();
    }
}
