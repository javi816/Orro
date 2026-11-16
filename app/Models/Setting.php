<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['monthly_savings_goal', 'minimum_savings_balance'])]
class Setting extends Model
{
    protected function casts(): array
    {
        return [
            'monthly_savings_goal' => 'decimal:2',
            'minimum_savings_balance' => 'decimal:2',
        ];
    }
}
