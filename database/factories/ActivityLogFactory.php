<?php

namespace Database\Factories;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\ActivityLog>
 */
class ActivityLogFactory extends Factory
{
    /**
     * @var class-string<\App\Models\ActivityLog>
     */
    protected $model = ActivityLog::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'affected_user_id' => User::factory(),
            'action' => fake()->randomElement([
                'request.created',
                'request.approved',
                'payment.submitted',
                'payment.approved',
                'clearance.updated',
            ]),
            'description' => fake()->sentence(),
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'metadata' => ['source' => fake()->word()],
            'created_at' => now(),
        ];
    }
}
