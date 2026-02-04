<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => '2547' . $this->faker->numerify('#########'),
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
            'credit_score' => $this->faker->numberBetween(300, 850),
            'status' => 'active',
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'suspended',
        ]);
    }

    public function flagged(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'flagged',
        ]);
    }
}