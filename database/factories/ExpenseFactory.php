<?php

namespace Database\Factories;

use App\Enums\ExpenseStatus;
use App\Enums\ExpenseType;
use App\Models\Expense;
use App\Models\Period;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expense>
 */
class ExpenseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'period_id' => Period::factory(),
            'expense_template_id' => null,
            'description' => fake()->words(3, true),
            'type' => ExpenseType::Fixed,
            'planned_amount' => '200.00',
            'actual_amount' => null,
            'expense_date' => null,
            'payment_number' => 1,
            'status' => ExpenseStatus::Pending,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn () => [
            'status' => ExpenseStatus::Paid,
            'expense_date' => now()->toDateString(),
        ]);
    }

    public function variable(): static
    {
        return $this->state(fn () => [
            'type' => ExpenseType::Variable,
            'expense_date' => now()->toDateString(),
        ]);
    }

    public function configure(): static
    {
        return $this->afterMaking(function (Expense $expense): void {
            if ($expense->status === ExpenseStatus::Paid && $expense->actual_amount === null) {
                $expense->actual_amount = $expense->planned_amount;
            }
        });
    }
}
