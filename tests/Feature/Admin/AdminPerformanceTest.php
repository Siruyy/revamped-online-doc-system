<?php

namespace Tests\Feature\Admin;

use App\Models\ActivityLog;
use App\Models\Clearance;
use App\Models\DocumentRequest;
use App\Models\DocumentType;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminPerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_and_request_list_keep_bounded_queries_with_data_volume(): void
    {
        [$admin] = $this->seedAdminVolume(students: 40, requests: 120);

        $dashboardQueries = $this->countQueries(fn () => $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk());

        $requestListQueries = $this->countQueries(fn () => $this->actingAs($admin)
            ->get(route('admin.requests.index', [
                'status' => 'pending',
            ]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Requests/Index')
                ->has('requests.data', 15)));

        $this->assertLessThanOrEqual(18, $dashboardQueries, "Admin dashboard used {$dashboardQueries} queries.");
        $this->assertLessThanOrEqual(8, $requestListQueries, "Admin request list used {$requestListQueries} queries.");
    }

    public function test_performance_check_command_seeds_volume_and_reports_query_counts(): void
    {
        $this->artisan('svci:performance-check', [
            '--students' => 8,
            '--requests' => 24,
            '--payments' => 24,
            '--clearances' => 24,
            '--logs' => 24,
        ])
            ->expectsOutputToContain('Seeded performance volume')
            ->expectsOutputToContain('Admin dashboard queries:')
            ->expectsOutputToContain('Admin request list queries:')
            ->assertExitCode(0);

        $this->assertSame(8, User::query()->where('role', 'student')->count());
        $this->assertSame(24, DocumentRequest::query()->count());
        $this->assertSame(24, Payment::query()->count());
        $this->assertSame(24, Clearance::query()->count());
        $this->assertSame(24, ActivityLog::query()->count());
    }

    public function test_request_list_document_type_filter_has_composite_index(): void
    {
        $indexes = collect(Schema::getIndexes('document_requests'));

        $this->assertTrue(
            $indexes->contains(fn (array $index): bool => $index['columns'] === ['status', 'document_type_id', 'created_at']),
            'Admin request list filters by status and document type while ordering by created_at.',
        );
    }

    /**
     * @return array{0: User}
     */
    private function seedAdminVolume(int $students, int $requests): array
    {
        $admin = User::factory()->admin()->create(['status' => 'active', 'email_verified_at' => now()]);
        $studentRows = User::factory()->student()->count($students)->sequence(
            fn ($sequence) => [
                'course' => ['BSIT', 'BSA', 'BSBA', 'BSED'][$sequence->index % 4],
                'year_level' => ($sequence->index % 4) + 1,
            ],
        )->create(['status' => 'active', 'email_verified_at' => now()]);
        $documentTypes = DocumentType::factory()->count(4)->create();

        DocumentRequest::factory()->count($requests)->sequence(
            fn ($sequence) => [
                'user_id' => $studentRows[$sequence->index % $studentRows->count()]->id,
                'document_type_id' => $documentTypes[$sequence->index % $documentTypes->count()]->id,
                'status' => ['pending', 'approved', 'denied', 'completed'][$sequence->index % 4],
                'processing_stage' => ['not_started', 'processing', 'ready_for_pickup', 'released'][$sequence->index % 4],
                'expected_release_on' => now()->subDays($sequence->index % 3)->toDateString(),
            ],
        )->create()->each(function (DocumentRequest $request) use ($admin): void {
            Payment::factory()->for($request->user)->for($request)->create(['status' => 'pending_approval']);
            Clearance::factory()->for($request->user)->for($request)->create();
            ActivityLog::factory()->create(['user_id' => $admin->id, 'affected_user_id' => $request->user_id]);
        });

        return [$admin];
    }

    private function countQueries(callable $callback): int
    {
        $queries = 0;

        DB::listen(function () use (&$queries): void {
            $queries++;
        });

        $callback();

        return $queries;
    }
}
