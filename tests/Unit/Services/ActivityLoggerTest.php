<?php

namespace Tests\Unit\Services;

use App\Models\ActivityLog;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class ActivityLoggerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_captures_actor_action_metadata_ip_and_user_agent(): void
    {
        $actor = User::factory()->admin()->create();
        $affectedUser = User::factory()->student()->create();
        $request = Request::create('/', 'GET', [], [], [], [
            'REMOTE_ADDR' => '203.0.113.10',
            'HTTP_USER_AGENT' => 'ServiceTest/1.0',
        ]);
        $this->app->instance('request', $request);

        ActivityLogger::log(
            'request_approved',
            'Approved request.',
            $actor,
            $affectedUser,
            ['document_request_id' => 123]
        );

        $log = ActivityLog::query()->firstOrFail();

        $this->assertSame($actor->id, $log->user_id);
        $this->assertSame($affectedUser->id, $log->affected_user_id);
        $this->assertSame('request_approved', $log->action);
        $this->assertSame('Approved request.', $log->description);
        $this->assertSame(['document_request_id' => 123], $log->metadata);
        $this->assertSame('203.0.113.10', $log->ip_address);
        $this->assertSame('ServiceTest/1.0', $log->user_agent);
    }
}
