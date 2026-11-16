<?php

namespace App\Repositories;

use App\Models\Period;
use App\Models\SavingsMovement;
use Illuminate\Database\Eloquent\Collection;

class SavingsMovementRepository
{
    /**
     * @return Collection<int, SavingsMovement>
     */
    public function all(): Collection
    {
        return SavingsMovement::query()
            ->with(['period', 'loan'])
            ->orderByDesc('movement_date')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * @return Collection<int, SavingsMovement>
     */
    public function forPeriod(Period $period): Collection
    {
        return SavingsMovement::query()
            ->where('period_id', $period->id)
            ->orderBy('movement_date')
            ->orderBy('id')
            ->get();
    }

    /**
     * @return Collection<int, SavingsMovement>
     */
    public function forLoan(int $loanId): Collection
    {
        return SavingsMovement::query()
            ->where('savings_loan_id', $loanId)
            ->orderBy('movement_date')
            ->orderBy('id')
            ->get();
    }

    /**
     * @return Collection<int, SavingsMovement>
     */
    public function forGoal(?int $goalId): Collection
    {
        $query = SavingsMovement::query()->with(['period', 'loan', 'goal']);

        if ($goalId === null) {
            $query->whereNull('savings_goal_id');
        } else {
            $query->where('savings_goal_id', $goalId);
        }

        return $query->orderByDesc('movement_date')->orderByDesc('id')->get();
    }

    public function hasOpeningBalance(): bool
    {
        return SavingsMovement::query()->where('type', 'opening_balance')->exists();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): SavingsMovement
    {
        return SavingsMovement::query()->create($attributes);
    }
}
