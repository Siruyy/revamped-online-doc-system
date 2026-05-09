<?php

namespace Database\Factories;

use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Message>
 */
class MessageFactory extends Factory
{
    /**
     * @var class-string<Message>
     */
    protected $model = Message::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sender_id' => User::factory(),
            'receiver_id' => User::factory(),
            'body' => fake()->paragraph(),
            'attachment_path' => null,
            'read_at' => null,
        ];
    }
}
