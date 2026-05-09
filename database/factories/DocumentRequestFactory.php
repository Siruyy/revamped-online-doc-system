<?php

namespace Database\Factories;

use App\Models\DocumentRequest;
use App\Models\DocumentType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\DocumentRequest>
 */
class DocumentRequestFactory extends Factory
{
    /**
     * @var class-string<DocumentRequest>
     */
    protected $model = DocumentRequest::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reference_no' => sprintf('REQ-%s-%06d', now()->format('Y'), fake()->unique()->numberBetween(1, 999999)),
            'user_id' => User::factory()->student(),
            'document_type_id' => DocumentType::factory(),
            'status' => 'pending',
            'processing_stage' => 'not_started',
            'denial_reason' => null,
            'approved_by' => null,
            'approved_at' => null,
            'released_at' => null,
            'purpose' => fake()->sentence(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => [
            'status' => 'pending',
            'processing_stage' => 'not_started',
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => 'approved',
            'processing_stage' => 'processing',
            'approved_by' => User::factory()->admin(),
            'approved_at' => now(),
            'denial_reason' => null,
        ]);
    }

    public function denied(): static
    {
        return $this->state(fn () => [
            'status' => 'denied',
            'processing_stage' => 'not_started',
            'approved_by' => User::factory()->admin(),
            'approved_at' => now(),
            'denial_reason' => fake()->sentence(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => 'completed',
            'processing_stage' => 'released',
            'approved_by' => User::factory()->admin(),
            'approved_at' => now()->subDays(2),
            'released_at' => now(),
        ]);
    }
}
