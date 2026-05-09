<?php

namespace App\Services\Policy;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Business-day SLA calculator per §13. Excludes weekends and configured
 * holidays.
 */
class SlaCalculator
{
    /**
     * Add a number of working days to the given start date.
     */
    public function addWorkingDays(CarbonInterface $start, int $days): CarbonImmutable
    {
        $date = CarbonImmutable::instance($start);

        $holidays = collect(config('policy.sla.holidays', []))
            ->map(fn ($h) => CarbonImmutable::parse($h)->toDateString())
            ->all();

        $added = 0;
        while ($added < $days) {
            $date = $date->addDay();
            if ($date->isWeekend()) {
                continue;
            }
            if (in_array($date->toDateString(), $holidays, true)) {
                continue;
            }
            $added++;
        }

        return $date;
    }

    /**
     * Return the expected release date (start + N working days).
     */
    public function expectedReleaseOn(CarbonInterface $startAt, int $slaDays): CarbonImmutable
    {
        return $this->addWorkingDays($startAt, $slaDays);
    }
}
