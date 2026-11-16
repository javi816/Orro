<?php

namespace App\Enums;

enum SavingsMovementType: string
{
    case OpeningBalance = 'opening_balance';
    case Deposit = 'deposit';
    case Withdrawal = 'withdrawal';
    case Repayment = 'repayment';

    public function label(): string
    {
        return match ($this) {
            self::OpeningBalance => 'Saldo inicial',
            self::Deposit => 'Aportación',
            self::Withdrawal => 'Retiro',
            self::Repayment => 'Reposición',
        };
    }

    public function sign(): int
    {
        return match ($this) {
            self::OpeningBalance, self::Deposit, self::Repayment => 1,
            self::Withdrawal => -1,
        };
    }
}
