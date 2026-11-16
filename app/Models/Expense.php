<?php

namespace App\Models;

use App\Enums\ExpenseStatus;
use App\Enums\ExpenseType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'period_id',
    'expense_template_id',
    'description',
    'type',
    'planned_amount',
    'actual_amount',
    'expense_date',
    'payment_number',
    'status',
])]
class Expense extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'type' => ExpenseType::class,
            'planned_amount' => 'decimal:2',
            'actual_amount' => 'decimal:2',
            'expense_date' => 'date',
            'payment_number' => 'integer',
            'status' => ExpenseStatus::class,
        ];
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(Period::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(ExpenseTemplate::class, 'expense_template_id');
    }
}
