<?php

namespace Database\Factories;

use App\Models\Faq;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Faq>
 */
class FaqFactory extends Factory
{
    /**
     * @var class-string<\App\Models\Faq>
     */
    protected $model = Faq::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'author_id' => User::factory()->admin(),
            'role' => fake()->randomElement(['student', 'staff', 'all']),
            'question' => fake()->sentence(12),
            'answer' => fake()->paragraph(),
            'sort_order' => fake()->numberBetween(0, 50),
        ];
    }
}
