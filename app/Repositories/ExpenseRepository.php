<?php

namespace App\Repositories;

use App\Models\Expense;
use App\Models\ExpenseTemplate;
use App\Models\Period;
use Illuminate\Database\Eloquent\Collection;

class ExpenseRepository
{
    /**
     * @return Collection<int, Expense>
     */
    public function forPeriod(Period $period): Collection
    {
        return Expense::query()
            ->with('template')
            ->where('period_id', $period->id)
            ->orderBy('type')
            ->orderBy('payment_number')
            ->orderBy('id')
            ->get();
    }

    public function find(int $id): Expense
    {
        return Expense::query()->with('period')->findOrFail($id);
    }

    public function existsForTemplate(Period $period, ExpenseTemplate $template, int $paymentNumber = 0): bool
    {
        $query = Expense::query()
            ->where('period_id', $period->id)
            ->where('expense_template_id', $template->id);

        if ($paymentNumber > 0) {
            $query->where('payment_number', $paymentNumber);
        }

        return $query->exists();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Expense
    {
        return Expense::query()->create($attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Expense $expense, array $attributes): Expense
    {
        $expense->update($attributes);

        return $expense->refresh();
    }
}
