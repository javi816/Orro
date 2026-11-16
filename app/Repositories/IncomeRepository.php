<?php

namespace App\Repositories;

use App\Models\Income;
use App\Models\IncomeSource;
use App\Models\Period;
use Illuminate\Database\Eloquent\Collection;

class IncomeRepository
{
    /**
     * @return Collection<int, Income>
     */
    public function forPeriod(Period $period): Collection
    {
        return Income::query()
            ->with('source')
            ->where('period_id', $period->id)
            ->orderBy('payment_number')
            ->orderBy('id')
            ->get();
    }

    public function find(int $id): Income
    {
        return Income::query()->with('period')->findOrFail($id);
    }

    public function existsForSource(Period $period, IncomeSource $source, int $paymentNumber = 0): bool
    {
        $query = Income::query()
            ->where('period_id', $period->id)
            ->where('income_source_id', $source->id);

        if ($paymentNumber > 0) {
            $query->where('payment_number', $paymentNumber);
        }

        return $query->exists();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Income
    {
        return Income::query()->create($attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Income $income, array $attributes): Income
    {
        $income->update($attributes);

        return $income->refresh();
    }
}
