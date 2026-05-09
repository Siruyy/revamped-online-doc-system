<?php

namespace Tests\Unit\Policy;

use App\Services\Policy\SlaCalculator;
use Carbon\Carbon;
use Tests\TestCase;

class SlaCalculatorTest extends TestCase
{
    public function test_skips_weekends(): void
    {
        $calc = new SlaCalculator();
        // Friday Apr 24, 2026 + 1 working day = Monday Apr 27, 2026
        $start = Carbon::parse('2026-04-24');
        $result = $calc->addWorkingDays($start, 1);

        $this->assertSame('2026-04-27', $result->toDateString());
    }

    public function test_excludes_configured_holidays(): void
    {
        config(['policy.sla.holidays' => ['2026-05-01']]);
        $calc = new SlaCalculator();
        // Apr 30 (Thu) + 1 wd should skip May 1 (holiday) + May 2-3 (weekend) → May 4
        $start = Carbon::parse('2026-04-30');
        $result = $calc->addWorkingDays($start, 1);

        $this->assertSame('2026-05-04', $result->toDateString());
    }

    public function test_expected_release_with_14_days(): void
    {
        $calc = new SlaCalculator();
        // Mon Apr 27 + 14 working days = Thu May 14, 2026
        $start = Carbon::parse('2026-04-27');
        $result = $calc->expectedReleaseOn($start, 14);

        $this->assertSame('2026-05-15', $result->toDateString());
    }
}
