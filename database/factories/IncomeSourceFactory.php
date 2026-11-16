<?php

namespace Database\Factories;

use App\Models\IncomeSource;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IncomeSource>
 */
class IncomeSourceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'description' => fake()->words(2, true),
            'amount' => '500.00',
            'payment_number' => 1,
            'active' => true,
        ];
    }
}
