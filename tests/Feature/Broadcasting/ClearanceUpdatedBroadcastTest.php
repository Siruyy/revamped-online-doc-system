<?php

namespace Tests\Feature\Broadcasting;

use App\Events\ClearanceUpdated;
use Illuminate\Broadcasting\PrivateChannel;
use Tests\TestCase;

class ClearanceUpdatedBroadcastTest extends TestCase
{
    public function test_broadcasts_to_matching_department_channel(): void
    {
        $event = new ClearanceUpdated(5, 10, 'teacher', 'signed', 'in_progress');

        $channels = collect($event->broadcastOn())
            ->map(fn (PrivateChannel $channel) => $channel->name)
            ->all();

        $this->assertContains('private-user.10', $channels);
        $this->assertContains('private-role.admin', $channels);
        $this->assertContains('private-role.department.teacher', $channels);
        $this->assertTrue($event->afterCommit);
    }
}
