<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['origin_period_id', 'due_period_id', 'amount', 'description'])]
class SavingsLoan extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    public function originPeriod(): BelongsTo
    {
        return $this->belongsTo(Period::class, 'origin_period_id');
    }

    public function duePeriod(): BelongsTo
    {
        return $this->belongsTo(Period::class, 'due_period_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(SavingsMovement::class);
    }
}
