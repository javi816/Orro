<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'monthly_goal', 'final_goal', 'active'])]
class SavingsGoal extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'monthly_goal' => 'decimal:2',
            'final_goal' => 'decimal:2',
            'active' => 'boolean',
        ];
    }

    public function movements(): HasMany
    {
        return $this->hasMany(SavingsMovement::class);
    }
}
