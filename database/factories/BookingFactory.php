<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\User;
use App\Models\Basecamp;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
        'user_id' => \App\Models\User::factory(),
        'basecamp_id' => \App\Models\Basecamp::factory(),
        // Sesuaikan nama kolom di bawah ini dengan kebutuhan database Anda
        'order_id' => 'BK-' . now()->format('Ymd') . '-' . strtoupper($this->faker->unique()->bothify('??##')),
        'tanggal_naik' => $this->faker->dateTimeBetween('+1 day', '+1 month')->format('Y-m-d'),
        'jumlah_pendaki' => $this->faker->numberBetween(1, 5),
        'total_price' => $this->faker->numberBetween(50000, 150000),
        'status' => $this->faker->randomElement(['pending', 'confirmed', 'canceled', 'completed']),
        
        // JIKA kolom checkout_by WAJIB diisi di database (bukan nullable), tambahkan ini:
        'checkout_by' => \App\Models\User::factory(),
        ];
    }
}
