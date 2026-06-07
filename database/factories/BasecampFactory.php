<?php

namespace Database\Factories;

use App\Models\Basecamp;
use App\Models\Gunung;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Basecamp>
 */
class BasecampFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */

    public function definition(): array
    {
        return [
            'nama' => 'Basecamp ' . $this->faker->word(),
            'admin_basecamp_id' => User::factory(),
            'gunung_id' => Gunung::factory(),
        ];
    }
}
