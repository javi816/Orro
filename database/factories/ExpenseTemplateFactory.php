<?php

namespace Database\Factories;

use App\Models\ExpenseTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExpenseTemplate>
 */
class ExpenseTemplateFactory extends Factory
{
    public function definition(): array
    {
        return [
            'description' => fake()->words(2, true),
            'amount' => '200.00',
            'payment_number' => 1,
            'active' => true,
        ];
    }
}
