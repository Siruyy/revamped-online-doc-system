<?php

namespace Tests\Unit\Models;

use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessageModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_unread_for_scope_returns_only_unread_messages_for_user(): void
    {
        $receiver = User::factory()->student()->create();
        $sender = User::factory()->admin()->create();
        $otherReceiver = User::factory()->student()->create();

        Message::factory()->create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'read_at' => null,
        ]);

        Message::factory()->create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'read_at' => now(),
        ]);

        Message::factory()->create([
            'sender_id' => $sender->id,
            'receiver_id' => $otherReceiver->id,
            'read_at' => null,
        ]);

        $unread = Message::query()->unreadFor($receiver)->get();

        $this->assertCount(1, $unread);
        $this->assertSame($receiver->id, $unread->first()->receiver_id);
        $this->assertNull($unread->first()->read_at);
    }
}
