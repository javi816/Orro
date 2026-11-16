<?php

namespace Database\Factories;

use App\Enums\SavingsMovementType;
use App\Models\Period;
use App\Models\SavingsMovement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SavingsMovement>
 */
class SavingsMovementFactory extends Factory
{
    public function definition(): array
    {
        return [
            'period_id' => Period::factory(),
            'savings_loan_id' => null,
            'type' => SavingsMovementType::Deposit,
            'amount' => '100.00',
            'description' => null,
            'movement_date' => now()->toDateString(),
        ];
    }
}
