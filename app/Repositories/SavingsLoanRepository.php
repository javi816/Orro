<?php

namespace App\Repositories;

use App\Models\Period;
use App\Models\SavingsLoan;
use Illuminate\Database\Eloquent\Collection;

class SavingsLoanRepository
{
    /**
     * @return Collection<int, SavingsLoan>
     */
    public function all(): Collection
    {
        return SavingsLoan::query()
            ->with(['originPeriod', 'duePeriod'])
            ->orderByDesc('id')
            ->get();
    }

    public function find(int $id): SavingsLoan
    {
        return SavingsLoan::query()
            ->with(['originPeriod', 'duePeriod'])
            ->findOrFail($id);
    }

    /**
     * @return Collection<int, SavingsLoan>
     */
    public function originatedIn(Period $period): Collection
    {
        return SavingsLoan::query()
            ->where('origin_period_id', $period->id)
            ->get();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): SavingsLoan
    {
        return SavingsLoan::query()->create($attributes);
    }
}
