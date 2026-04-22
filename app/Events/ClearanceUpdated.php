<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ClearanceUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public int $clearanceId,
        public int $studentId,
        public string $department,
        public string $action,
        public ?string $overallStatus = null,
    ) {}
}
