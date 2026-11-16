<?php

namespace App\Models;

use App\Enums\PeriodStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

#[Fillable(['month', 'year', 'variable_budget', 'status', 'closed_at', 'closing_summary'])]
class Period extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'month' => 'integer',
            'year' => 'integer',
            'variable_budget' => 'decimal:2',
            'status' => PeriodStatus::class,
            'closed_at' => 'datetime',
            'closing_summary' => 'array',
        ];
    }

    public function incomes(): HasMany
    {
        return $this->hasMany(Income::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function savingsMovements(): HasMany
    {
        return $this->hasMany(SavingsMovement::class);
    }

    public function originatedLoans(): HasMany
    {
        return $this->hasMany(SavingsLoan::class, 'origin_period_id');
    }

    public function dueLoans(): HasMany
    {
        return $this->hasMany(SavingsLoan::class, 'due_period_id');
    }

    public function label(): string
    {
        return ucfirst(Carbon::createFromDate($this->year, $this->month, 1)->locale('es')->translatedFormat('F Y'));
    }

    public function shortLabel(): string
    {
        return ucfirst(Carbon::createFromDate($this->year, $this->month, 1)->locale('es')->translatedFormat('M Y'));
    }

    public function isClosed(): bool
    {
        return $this->status === PeriodStatus::Closed;
    }
}
