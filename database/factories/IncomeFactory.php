<?php

namespace Database\Factories;

use App\Enums\IncomeStatus;
use App\Models\Income;
use App\Models\Period;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Income>
 */
class IncomeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'period_id' => Period::factory(),
            'income_source_id' => null,
            'description' => fake()->words(3, true),
            'amount' => '500.00',
            'received_at' => null,
            'payment_number' => 1,
            'status' => IncomeStatus::Pending,
        ];
    }

    public function received(): static
    {
        return $this->state(fn () => [
            'status' => IncomeStatus::Received,
            'received_at' => now()->toDateString(),
        ]);
    }
}
