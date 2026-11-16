<?php

namespace App\Enums;

enum IncomeStatus: string
{
    case Pending = 'pending';
    case Received = 'received';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendiente',
            self::Received => 'Recibido',
        };
    }
}
