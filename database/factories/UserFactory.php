<?php

namespace Database\Factories;

use App\Models\Carrera;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
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
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'rol' => User::ROL_DIRECTOR_CARRERA,
            'carrera_id' => Carrera::factory(),
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

    public function director(Carrera|int|null $carrera = null): static
    {
        return $this->state(fn () => [
            'rol' => User::ROL_DIRECTOR_CARRERA,
            'carrera_id' => $carrera ?? Carrera::factory(),
        ]);
    }

    public function vicerrectorado(): static
    {
        return $this->state(fn () => [
            'rol' => User::ROL_VICERRECTORADO,
            'carrera_id' => null,
        ]);
    }
}
