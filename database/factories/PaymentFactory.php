<?php

namespace Database\Factories;

use App\Models\DocumentRequest;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * @var class-string<Payment>
     */
    protected $model = Payment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->student(),
            'document_request_id' => DocumentRequest::factory(),
            'total_amount' => fake()->randomFloat(2, 100, 2500),
            'receipt_path' => null,
            'payment_method' => fake()->randomElement(['Cash', 'GCash', 'Bank Transfer']),
            'reference_number' => strtoupper(fake()->bothify('REF-####-????')),
            'status' => 'pending',
            'denial_reason' => null,
            'approved_by' => null,
            'approved_at' => null,
            'submitted_at' => now(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => [
            'status' => 'pending',
            'approved_by' => null,
            'approved_at' => null,
            'denial_reason' => null,
        ]);
    }

    public function pendingApproval(): static
    {
        return $this->state(fn () => [
            'status' => 'pending_approval',
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => 'approved',
            'approved_by' => User::factory()->admin(),
            'approved_at' => now(),
            'denial_reason' => null,
        ]);
    }

    public function denied(): static
    {
        return $this->state(fn () => [
            'status' => 'denied',
            'approved_by' => User::factory()->admin(),
            'approved_at' => now(),
            'denial_reason' => fake()->sentence(),
        ]);
    }
}
