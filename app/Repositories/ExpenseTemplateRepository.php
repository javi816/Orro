<?php

namespace App\Repositories;

use App\Models\ExpenseTemplate;
use Illuminate\Database\Eloquent\Collection;

class ExpenseTemplateRepository
{
    /**
     * @return Collection<int, ExpenseTemplate>
     */
    public function all(): Collection
    {
        return ExpenseTemplate::query()->orderBy('description')->get();
    }

    /**
     * @return Collection<int, ExpenseTemplate>
     */
    public function active(): Collection
    {
        return ExpenseTemplate::query()
            ->where('active', true)
            ->orderBy('payment_number')
            ->orderBy('description')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): ExpenseTemplate
    {
        return ExpenseTemplate::query()->create($attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(ExpenseTemplate $template, array $attributes): ExpenseTemplate
    {
        $template->update($attributes);

        return $template->refresh();
    }
}
