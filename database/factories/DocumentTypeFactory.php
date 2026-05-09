<?php

namespace Database\Factories;

use App\Models\DocumentType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\DocumentType>
 */
class DocumentTypeFactory extends Factory
{
    /**
     * @var class-string<DocumentType>
     */
    protected $model = DocumentType::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement([
                'Transcript of Records',
                'Good Moral Certificate',
                'Certificate of Enrollment',
                'Diploma',
                'Honorable Dismissal',
            ]),
            'description' => fake()->sentence(),
            'category' => fake()->randomElement(['Academic', 'Clearance', 'Records']),
            'fee' => fake()->randomFloat(2, 50, 500),
            'fee_formula' => 'per_page',
            'default_page_count' => fake()->numberBetween(1, 10),
            'processing_days' => fake()->numberBetween(1, 10),
            'is_active' => true,
        ];
    }
}
