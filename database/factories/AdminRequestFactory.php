<?php

namespace Database\Factories;

use App\Models\AdminRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AdminRequest>
 */
class AdminRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'request_type' => $this->faker->randomElement(['admin_gunung', 'admin_basecamp']),
            'status' => 'pending',
            'email' => $this->faker->safeEmail(),
        ];
    }
}
