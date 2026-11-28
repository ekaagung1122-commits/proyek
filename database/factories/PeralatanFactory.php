<?php

namespace Database\Factories;

use App\Models\Basecamp;
use App\Models\KategoriPeralatan;
use App\Models\Peralatan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Peralatan>
 */
class PeralatanFactory extends Factory
{
    protected $model = Peralatan::class;

    public function definition(): array
    {
        return [
            'basecamp_id' => Basecamp::factory(),
            'kategori_id' => KategoriPeralatan::factory(),
            'nama_alat' => $this->faker->randomElement(['Tenda Dome 2P', 'Carrier 60L', 'Sleeping Bag', 'Kompor Portable']),
            'deskripsi' => $this->faker->sentence(),
            'harga_sewa' => $this->faker->numberBetween(15000, 75000),
            'stok' => $this->faker->numberBetween(1, 20),
            'foto' => null,
        ];
    }
}