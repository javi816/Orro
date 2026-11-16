<?php

namespace Database\Factories;

use App\Enums\PeriodStatus;
use App\Models\Period;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Period>
 */
class PeriodFactory extends Factory
{
    protected static int $sequence = 0;

    public function definition(): array
    {
        $index = static::$sequence++;

        return [
            'year' => 2040 + intdiv($index, 12),
            'month' => ($index % 12) + 1,
            'variable_budget' => '0.00',
            'status' => PeriodStatus::Open,
        ];
    }

    public function closed(): static
    {
        return $this->state(fn () => [
            'status' => PeriodStatus::Closed,
            'closed_at' => now(),
        ]);
    }
}
