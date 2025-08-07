<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
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

            'firstName' => $this->faker->firstName(),
            'lastName' => $this->faker->lastName(),
            'userName' => $this->faker->unique()->userName(),
            'email' => $this->faker->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => Hash::make('password123@'), // or Hash::make('password')
            'remember_token' => Str::random(10),
            'gender' => 'male',
            'country' => 'USA',
            'city' => 'New York',
            'birthday' => '1990-01-01',
           'phone' => '0' . $this->faker->unique()->randomNumber(9, true), 
            'accountType' => '20',
            'status' => '1',
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn(array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
