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
        string $deanStatus,
        string $presidentStatus,
        string $librarianStatus,
        string $studentAffairsStatus,
        string $alumniStatus,
        string $guidanceStatus,
        bool $expected
    ): void {
        $clearance = Clearance::factory()->make([
            'dean_status' => $deanStatus,
            'president_status' => $presidentStatus,
            'librarian_status' => $librarianStatus,
            'student_affairs_status' => $studentAffairsStatus,
            'alumni_status' => $alumniStatus,
            'guidance_status' => $guidanceStatus,
        ]);

        $this->assertSame($expected, $clearance->isComplete());
    }

    public static function clearanceStatusCombinations(): array
    {
        $statuses = ['pending', 'cleared'];
        $datasets = [];

        $signatories = ['dean', 'president', 'librarian', 'student_affairs', 'alumni', 'guidance'];

        for ($mask = 0; $mask < (2 ** count($signatories)); $mask++) {
            $row = [];

            foreach ($signatories as $index => $signatory) {
                $row[$signatory] = ($mask & (1 << $index)) ? 'cleared' : 'pending';
            }

            $datasets[] = [
                $row['dean'],
                $row['president'],
                $row['librarian'],
                $row['student_affairs'],
                $row['alumni'],
                $row['guidance'],
                collect($row)->every(fn (string $status): bool => $status === 'cleared'),
            ];
        }

        return $datasets;
    }

    public function test_recompute_overall_status_sets_denied_when_any_department_denies(): void
    {
        $clearance = Clearance::factory()->make([
            'dean_status' => 'denied',
            'president_status' => 'pending',
            'librarian_status' => 'pending',
            'student_affairs_status' => 'pending',
            'alumni_status' => 'pending',
            'guidance_status' => 'pending',
            'overall_status' => 'in_progress',
        ]);

        $clearance->recomputeOverallStatus();

        $this->assertSame('denied', $clearance->overall_status);
        $this->assertNull($clearance->completed_at);
    }

    public function test_recompute_overall_status_sets_completed_when_all_cleared(): void
    {
        $clearance = Clearance::factory()->make([
            'dean_status' => 'cleared',
            'president_status' => 'cleared',
            'librarian_status' => 'cleared',
            'student_affairs_status' => 'cleared',
            'alumni_status' => 'cleared',
            'guidance_status' => 'cleared',
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
            'dean_status' => 'pending',
            'president_status' => 'cleared',
            'librarian_status' => 'cleared',
            'student_affairs_status' => 'cleared',
            'alumni_status' => 'cleared',
            'guidance_status' => 'cleared',
            'overall_status' => 'completed',
            'completed_at' => now(),
        ]);

        $clearance->recomputeOverallStatus();

        $this->assertSame('in_progress', $clearance->overall_status);
        $this->assertNull($clearance->completed_at);
    }
}
