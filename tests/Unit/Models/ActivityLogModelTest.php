<?php

namespace Tests\Unit\Models;

use App\Models\ActivityLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityLogModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_activity_log_can_be_created_without_request_context(): void
    {
        $this->app->forgetInstance('request');

        $log = new ActivityLog([
            'action' => 'system_event',
            'description' => 'Created outside HTTP context.',
        ]);

        $log->save();

        $this->assertNull($log->ip_address);
        $this->assertNull($log->user_agent);
    }
}
