<?php

namespace App\Models;

use App\Enums\SavingsMovementType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['period_id', 'savings_goal_id', 'savings_loan_id', 'type', 'amount', 'description', 'movement_date'])]
class SavingsMovement extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'type' => SavingsMovementType::class,
            'amount' => 'decimal:2',
            'movement_date' => 'date',
        ];
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(Period::class);
    }

    public function goal(): BelongsTo
    {
        return $this->belongsTo(SavingsGoal::class, 'savings_goal_id');
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(SavingsLoan::class, 'savings_loan_id');
    }
}
