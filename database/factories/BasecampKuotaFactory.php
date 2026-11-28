<?php

namespace Database\Factories;

use App\Models\Basecamp;
use App\Models\BasecampKuota;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BasecampKuota>
 */
class BasecampKuotaFactory extends Factory
{
    protected $model = BasecampKuota::class;

    public function definition(): array
    {
        return [
            'basecamp_id' => Basecamp::factory(),
            'tanggal' => now()->addDays(3)->format('Y-m-d'),
            'kuota' => 50,
            'kuota_terpakai' => 0,
        ];
    }
}