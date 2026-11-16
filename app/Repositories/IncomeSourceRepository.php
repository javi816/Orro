<?php

namespace App\Repositories;

use App\Models\IncomeSource;
use Illuminate\Database\Eloquent\Collection;

class IncomeSourceRepository
{
    /**
     * @return Collection<int, IncomeSource>
     */
    public function all(): Collection
    {
        return IncomeSource::query()->orderBy('description')->get();
    }

    /**
     * @return Collection<int, IncomeSource>
     */
    public function active(): Collection
    {
        return IncomeSource::query()
            ->where('active', true)
            ->orderBy('payment_number')
            ->orderBy('description')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): IncomeSource
    {
        return IncomeSource::query()->create($attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(IncomeSource $source, array $attributes): IncomeSource
    {
        $source->update($attributes);

        return $source->refresh();
    }
}
