<?php

namespace Database\Factories;

use App\Models\Clearance;
use App\Models\DocumentRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Clearance>
 */
class ClearanceFactory extends Factory
{
    /**
     * @var class-string<Clearance>
     */
    protected $model = Clearance::class;

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
            'teacher_status' => 'pending',
            'teacher_remarks' => null,
            'teacher_signed_by' => null,
            'teacher_signed_at' => null,
            'dean_status' => 'pending',
            'dean_remarks' => null,
            'dean_signed_by' => null,
            'dean_signed_at' => null,
            'accounting_status' => 'pending',
            'accounting_remarks' => null,
            'accounting_signed_by' => null,
            'accounting_signed_at' => null,
            'sao_status' => 'pending',
            'sao_remarks' => null,
            'sao_signed_by' => null,
            'sao_signed_at' => null,
            'overall_status' => 'in_progress',
            'completed_at' => null,
            'pdf_path' => null,
            'uploaded_file_path' => null,
        ];
    }

    public function inProgress(): static
    {
        return $this->state(fn () => [
            'overall_status' => 'in_progress',
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'teacher_status' => 'cleared',
            'dean_status' => 'cleared',
            'accounting_status' => 'cleared',
            'sao_status' => 'cleared',
            'overall_status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    public function denied(): static
    {
        return $this->state(fn () => [
            'teacher_status' => fake()->randomElement(['denied', 'cleared']),
            'dean_status' => fake()->randomElement(['denied', 'cleared']),
            'accounting_status' => fake()->randomElement(['denied', 'cleared']),
            'sao_status' => fake()->randomElement(['denied', 'cleared']),
            'overall_status' => 'denied',
            'completed_at' => null,
        ]);
    }
}
