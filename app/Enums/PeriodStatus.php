<?php

namespace App\Enums;

enum PeriodStatus: string
{
    case Open = 'open';
    case ReadyToClose = 'ready_to_close';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Abierto',
            self::ReadyToClose => 'Listo para cerrar',
            self::Closed => 'Cerrado',
        };
    }

    public function isClosed(): bool
    {
        return $this === self::Closed;
    }
}
