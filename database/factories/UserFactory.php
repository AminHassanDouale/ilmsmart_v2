<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        $firstName = fake()->firstName();
        $lastName  = fake()->lastName();

        return [
            'name'              => $firstName . ' ' . $lastName,
            'first_name'        => $firstName,
            'last_name'         => $lastName,
            'username'          => fake()->unique()->userName(),
            'email'             => fake()->unique()->safeEmail(),
            'phone'             => fake()->phoneNumber(),
            'email_verified_at' => now(),
            'password'          => static::$password ??= Hash::make('password'),
            'remember_token'    => Str::random(10),
            'role'              => 'student',
            'status'            => 'active',
            'language'          => 'fr',
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn(array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn(array $attributes) => ['role' => 'admin']);
    }

    public function teacher(): static
    {
        return $this->state(fn(array $attributes) => ['role' => 'teacher']);
    }

    public function parent(): static
    {
        return $this->state(fn(array $attributes) => ['role' => 'parent']);
    }
}
