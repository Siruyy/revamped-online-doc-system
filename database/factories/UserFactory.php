<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * @var class-string<\App\Models\User>
     */
    protected $model = User::class;

    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'fullname' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => 'student',
            'status' => 'active',
            'course' => fake()->randomElement(['BSIT', 'BSA', 'BSBA', 'BSED']),
            'year_level' => fake()->numberBetween(1, 4),
            'student_id' => 'SVCI-'.fake()->unique()->numerify('######'),
            'contact_number' => fake()->phoneNumber(),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function student(): static
    {
        return $this->state(fn () => [
            'role' => 'student',
            'course' => fake()->randomElement(['BSIT', 'BSA', 'BSBA', 'BSED']),
            'year_level' => fake()->numberBetween(1, 4),
            'student_id' => 'SVCI-'.fake()->unique()->numerify('######'),
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn () => [
            'role' => 'admin',
            'course' => null,
            'year_level' => null,
            'student_id' => null,
        ]);
    }

    public function teacher(): static
    {
        return $this->state(fn () => [
            'role' => 'teacher',
            'course' => null,
            'year_level' => null,
            'student_id' => null,
        ]);
    }

    public function dean(): static
    {
        return $this->state(fn () => [
            'role' => 'dean',
            'course' => null,
            'year_level' => null,
            'student_id' => null,
        ]);
    }

    public function accounting(): static
    {
        return $this->state(fn () => [
            'role' => 'accounting',
            'course' => null,
            'year_level' => null,
            'student_id' => null,
        ]);
    }

    public function sao(): static
    {
        return $this->state(fn () => [
            'role' => 'sao',
            'course' => null,
            'year_level' => null,
            'student_id' => null,
        ]);
    }

    public function superadmin(): static
    {
        return $this->state(fn () => [
            'role' => 'superadmin',
            'course' => null,
            'year_level' => null,
            'student_id' => null,
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn () => [
            'status' => 'pending',
        ]);
    }

    public function suspended(): static
    {
        return $this->state(fn () => [
            'status' => 'suspended',
        ]);
    }
}
