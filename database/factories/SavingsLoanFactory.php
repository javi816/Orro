<?php

namespace Database\Factories;

use App\Models\Period;
use App\Models\SavingsLoan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SavingsLoan>
 */
class SavingsLoanFactory extends Factory
{
    public function definition(): array
    {
        return [
            'origin_period_id' => Period::factory(),
            'due_period_id' => Period::factory(),
            'amount' => '150.00',
            'description' => fake()->words(3, true),
        ];
    }
}
