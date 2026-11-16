<?php

namespace App\Repositories;

use App\Enums\PeriodStatus;
use App\Models\Period;
use Illuminate\Database\Eloquent\Collection;

class PeriodRepository
{
    public function find(int $id): Period
    {
        return Period::query()->findOrFail($id);
    }

    public function findByYearMonth(int $year, int $month): ?Period
    {
        return Period::query()
            ->where('year', $year)
            ->where('month', $month)
            ->first();
    }

    public function firstOrCreate(int $year, int $month): Period
    {
        return Period::query()->firstOrCreate(
            ['year' => $year, 'month' => $month],
            ['variable_budget' => '0.00', 'status' => PeriodStatus::Open]
        );
    }

    /**
     * @return Collection<int, Period>
     */
    public function all(): Collection
    {
        return Period::query()
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get();
    }

    /**
     * @return Collection<int, Period>
     */
    public function allAscending(): Collection
    {
        return Period::query()
            ->orderBy('year')
            ->orderBy('month')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Period $period, array $attributes): Period
    {
        $period->update($attributes);

        return $period->refresh();
    }
}
