<?php

namespace Database\Factories;

use App\Models\KategoriPeralatan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KategoriPeralatan>
 */
class KategoriPeralatanFactory extends Factory
{
    protected $model = KategoriPeralatan::class;

    public function definition(): array
    {
        return [
            'nama_kategori' => $this->faker->unique()->randomElement(['Tenda', 'Carrier', 'Sleeping Bag', 'Kompor', 'Jaket']),
        ];
    }
}