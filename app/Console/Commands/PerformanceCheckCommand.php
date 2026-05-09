<?php

namespace App\Console\Commands;

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\RequestController;
use App\Models\ActivityLog;
use App\Models\Clearance;
use App\Models\DocumentRequest;
use App\Models\DocumentType;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PerformanceCheckCommand extends Command
{
    protected $signature = 'svci:performance-check
        {--students=1000 : Student accounts to seed}
        {--requests=5000 : Document requests to seed}
        {--payments=5000 : Payments to seed}
        {--clearances=5000 : Clearances to seed}
        {--logs=5000 : Activity logs to seed}';

    protected $description = 'Opt-in production-like data volume seed and admin query-count check';

    public function handle(DashboardController $dashboardController, RequestController $requestController): int
    {
        $students = max(1, (int) $this->option('students'));
        $requests = max(1, (int) $this->option('requests'));
        $payments = max(1, (int) $this->option('payments'));
        $clearances = max(1, (int) $this->option('clearances'));
        $logs = max(1, (int) $this->option('logs'));

        $runId = now()->format('YmdHis').'-'.Str::lower(Str::random(6));
        $admin = $this->seedStaffUser();
        $studentIds = $this->seedStudents($students, $runId);
        $documentTypeIds = $this->seedDocumentTypes();
        $requestIds = $this->seedRequests($requests, $studentIds, $documentTypeIds, $runId);

        $this->seedPayments($payments, $studentIds, $requestIds);
        $this->seedClearances($clearances, $studentIds, $requestIds);
        $this->seedActivityLogs($logs, $admin->id, $studentIds);

        Auth::login($admin);

        $dashboardQueries = $this->countQueries(fn () => $dashboardController->index());
        $requestListQueries = $this->countQueries(fn () => $requestController->index(Request::create('/admin/requests', 'GET', [
            'status' => 'pending',
        ])));

        $this->info('Seeded performance volume: '.$students.' students, '.$requests.' requests, '.$payments.' payments, '.$clearances.' clearances, '.$logs.' logs.');
        $this->info('Admin dashboard queries: '.$dashboardQueries);
        $this->info('Admin request list queries: '.$requestListQueries);

        return self::SUCCESS;
    }

    private function seedStaffUser(): User
    {
        return User::query()->updateOrCreate(
            ['email' => 'performance-admin@example.test'],
            [
                'fullname' => 'Performance Admin',
                'password' => Hash::make(Str::password(16)),
                'role' => 'admin',
                'status' => 'active',
                'email_verified_at' => now(),
                'approved_at' => now(),
            ],
        );
    }

    /**
     * @return list<int>
     */
    private function seedStudents(int $count, string $runId): array
    {
        User::factory()->student()->count($count)->sequence(
            fn ($sequence) => [
                'course' => ['BSIT', 'BSA', 'BSBA', 'BSED'][$sequence->index % 4],
                'year_level' => ($sequence->index % 4) + 1,
                'email' => 'perf-student-'.$runId.'-'.$sequence->index.'@example.test',
                'student_id' => 'PERF-'.Str::upper(Str::replace('-', '', $runId)).'-'.str_pad((string) ($sequence->index + 1), 6, '0', STR_PAD_LEFT),
            ],
        )->create(['status' => 'active', 'email_verified_at' => now()]);

        return User::query()
            ->where('role', 'student')
            ->latest('id')
            ->limit($count)
            ->pluck('id')
            ->all();
    }

    /**
     * @return list<int>
     */
    private function seedDocumentTypes(): array
    {
        if (DocumentType::query()->count() < 4) {
            DocumentType::factory()->count(4)->create();
        }

        return DocumentType::query()->limit(8)->pluck('id')->all();
    }

    /**
     * @param  list<int>  $studentIds
     * @param  list<int>  $documentTypeIds
     * @return list<int>
     */
    private function seedRequests(int $count, array $studentIds, array $documentTypeIds, string $runId): array
    {
        DocumentRequest::factory()->count($count)->sequence(
            fn ($sequence) => [
                'reference_no' => sprintf('PERF-%s-%06d', Str::upper(Str::replace('-', '', $runId)), $sequence->index + 1),
                'user_id' => $studentIds[$sequence->index % count($studentIds)],
                'document_type_id' => $documentTypeIds[$sequence->index % count($documentTypeIds)],
                'status' => ['pending', 'approved', 'denied', 'completed'][$sequence->index % 4],
                'processing_stage' => ['not_started', 'processing', 'ready_for_pickup', 'released'][$sequence->index % 4],
                'expected_release_on' => now()->subDays($sequence->index % 14)->toDateString(),
            ],
        )->create();

        return DocumentRequest::query()->latest('id')->limit($count)->pluck('id')->all();
    }

    /**
     * @param  list<int>  $studentIds
     * @param  list<int>  $requestIds
     */
    private function seedPayments(int $count, array $studentIds, array $requestIds): void
    {
        Payment::factory()->count($count)->sequence(
            fn ($sequence) => [
                'user_id' => $studentIds[$sequence->index % count($studentIds)],
                'document_request_id' => $requestIds[$sequence->index % count($requestIds)],
                'status' => ['pending', 'pending_approval', 'approved', 'denied'][$sequence->index % 4],
            ],
        )->create();
    }

    /**
     * @param  list<int>  $studentIds
     * @param  list<int>  $requestIds
     */
    private function seedClearances(int $count, array $studentIds, array $requestIds): void
    {
        Clearance::factory()->count($count)->sequence(
            fn ($sequence) => [
                'user_id' => $studentIds[$sequence->index % count($studentIds)],
                'document_request_id' => $requestIds[$sequence->index % count($requestIds)],
                'overall_status' => ['in_progress', 'completed', 'denied'][$sequence->index % 3],
            ],
        )->create();
    }

    /**
     * @param  list<int>  $studentIds
     */
    private function seedActivityLogs(int $count, int $adminId, array $studentIds): void
    {
        ActivityLog::factory()->count($count)->sequence(
            fn ($sequence) => [
                'user_id' => $adminId,
                'affected_user_id' => $studentIds[$sequence->index % count($studentIds)],
            ],
        )->create();
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
