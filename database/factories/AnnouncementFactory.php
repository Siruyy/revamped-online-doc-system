<?php

namespace Database\Factories;

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Announcement>
 */
class AnnouncementFactory extends Factory
{
    /**
     * @var class-string<\App\Models\Announcement>
     */
    protected $model = Announcement::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'author_id' => User::factory()->admin(),
            'title' => fake()->sentence(6),
            'body' => fake()->paragraphs(2, true),
            'audience' => fake()->randomElement(['all', 'student', 'staff']),
            'pinned' => fake()->boolean(20),
            'published_at' => now(),
        ];
    }
}
