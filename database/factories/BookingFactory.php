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
            // Menghubungkan booking dengan user (pendaki) secara otomatis
            'user_id' => User::factory(),
            
            // Menghubungkan booking dengan basecamp secara otomatis
            'basecamp_id' => Basecamp::factory(),
            
            // Kode booking unik, contoh: BK-20260608-XYZ12
            'order_id' => 'BK-' . now()->format('Ymd') . '-' . strtoupper($this->faker->unique()->bothify('??##')),
            
            // Tanggal mendaki (misal antara besok sampai 1 bulan ke depan)
            'tanggal_naik' => $this->faker->dateTimeBetween('+1 day', '+1 month')->format('Y-m-d'),
            
            // Jumlah anggota kelompok pendaki
            'jumlah_pendaki' => $this->faker->numberBetween(1, 10),
            
            // Total harga bayar
            'total_price' => $this->faker->numberBetween(50000, 250000),
            
            // Status booking secara default saat dibuat untuk testing
            'status' => $this->faker->randomElement(['pending', 'success', 'canceled']),
        ];
    }
}
