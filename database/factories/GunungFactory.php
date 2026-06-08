<?php

namespace Database\Factories;

use App\Models\Gunung;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Gunung>
 */
class GunungFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama' => 'Gunung ' . $this->faker->unique()->randomElement(['Semeru', 'Rinjani', 'Merbabu', 'Prau', 'Slamet', 'Lawu', 'Papandayan']),
            'lokasi' => $this->faker->state() . ', Indonesia',
            'deskripsi' => $this->faker->paragraph(),
            'created_by' => User::factory(),
            'status' => 1,
            'ketinggian' => $this->faker->numberBetween(1000, 3676),
        ];
    }
}
