<?php

namespace App\Services;

use App\Enums\SavingsMovementType;
use App\Models\Period;
use App\Models\SavingsMovement;
use App\Repositories\SavingsMovementRepository;
use App\Support\Money;
use DomainException;
use Illuminate\Database\Eloquent\Collection;

class SavingsService
{
    public function __construct(
        private readonly SavingsMovementRepository $movements,
        private readonly FinanceService $finance,
    ) {}

    /**
     * @return Collection<int, SavingsMovement>
     */
    public function movements(): Collection
    {
        return $this->movements->all();
    }

    public function balance(): string
    {
        return $this->finance->savingsBalance();
    }

    public function balanceForGoal(?int $goalId): string
    {
        return $this->finance->savingsBalanceForGoal($goalId);
    }

    /**
     * @return Collection<int, SavingsMovement>
     */
    public function movementsForGoal(?int $goalId): Collection
    {
        return $this->movements->forGoal($goalId);
    }

    public function hasOpeningBalance(): bool
    {
        return $this->movements->hasOpeningBalance();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function register(Period $period, array $data): SavingsMovement
    {
        if ($period->isClosed()) {
            throw new DomainException('El período está cerrado y no admite modificaciones.');
        }

        $type = SavingsMovementType::from($data['type']);
        $amount = Money::normalize($data['amount']);

        if (! Money::isPositive($amount)) {
            throw new DomainException('El importe del movimiento debe ser mayor que cero.');
        }

        if (! in_array($type, [SavingsMovementType::OpeningBalance, SavingsMovementType::Deposit, SavingsMovementType::Withdrawal], true)) {
            throw new DomainException('Las reposiciones se registran desde el módulo de préstamos.');
        }

        if ($type === SavingsMovementType::OpeningBalance && $this->movements->hasOpeningBalance()) {
            throw new DomainException('El saldo inicial ya fue registrado.');
        }

        if ($type === SavingsMovementType::Withdrawal && Money::compare($amount, $this->finance->savingsBalance()) > 0) {
            throw new DomainException('El retiro no puede superar el saldo acumulado del ahorro.');
        }

        return $this->movements->create([
            'period_id' => $period->id,
            'savings_goal_id' => $data['savings_goal_id'] ?? null,
            'savings_loan_id' => null,
            'type' => $type,
            'amount' => $amount,
            'description' => $data['description'] ?? null,
            'movement_date' => $data['movement_date'] ?? now()->toDateString(),
        ]);
    }
}
