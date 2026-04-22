<?php

namespace Tests\Unit\Models;

use App\Models\Clearance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ClearanceModelTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('clearanceStatusCombinations')]
    public function test_is_complete_returns_expected_result(
        string $teacherStatus,
        string $deanStatus,
        string $accountingStatus,
        string $saoStatus,
        bool $expected
    ): void {
        $clearance = Clearance::factory()->make([
            'teacher_status' => $teacherStatus,
            'dean_status' => $deanStatus,
            'accounting_status' => $accountingStatus,
            'sao_status' => $saoStatus,
        ]);

        $this->assertSame($expected, $clearance->isComplete());
    }

    public static function clearanceStatusCombinations(): array
    {
        $statuses = ['pending', 'cleared'];
        $datasets = [];

        foreach ($statuses as $teacher) {
            foreach ($statuses as $dean) {
                foreach ($statuses as $accounting) {
                    foreach ($statuses as $sao) {
                        $expected = $teacher === 'cleared'
                            && $dean === 'cleared'
                            && $accounting === 'cleared'
                            && $sao === 'cleared';

                        $datasets[] = [$teacher, $dean, $accounting, $sao, $expected];
                    }
                }
            }
        }

        return $datasets;
    }

    public function test_recompute_overall_status_sets_denied_when_any_department_denies(): void
    {
        $clearance = Clearance::factory()->make([
            'teacher_status' => 'cleared',
            'dean_status' => 'denied',
            'accounting_status' => 'pending',
            'sao_status' => 'pending',
            'overall_status' => 'in_progress',
        ]);

        $clearance->recomputeOverallStatus();

        $this->assertSame('denied', $clearance->overall_status);
        $this->assertNull($clearance->completed_at);
    }

    public function test_recompute_overall_status_sets_completed_when_all_cleared(): void
    {
        $clearance = Clearance::factory()->make([
            'teacher_status' => 'cleared',
            'dean_status' => 'cleared',
            'accounting_status' => 'cleared',
            'sao_status' => 'cleared',
            'overall_status' => 'in_progress',
            'completed_at' => null,
        ]);

        $clearance->recomputeOverallStatus();

        $this->assertSame('completed', $clearance->overall_status);
        $this->assertNotNull($clearance->completed_at);
    }

    public function test_recompute_overall_status_sets_in_progress_when_not_complete_and_not_denied(): void
    {
        $clearance = Clearance::factory()->make([
            'teacher_status' => 'cleared',
            'dean_status' => 'pending',
            'accounting_status' => 'pending',
            'sao_status' => 'cleared',
            'overall_status' => 'completed',
            'completed_at' => now(),
        ]);

        $clearance->recomputeOverallStatus();

        $this->assertSame('in_progress', $clearance->overall_status);
        $this->assertNull($clearance->completed_at);
    }
}
