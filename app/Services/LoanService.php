<?php

namespace App\Services;

use App\Enums\SavingsMovementType;
use App\Models\Period;
use App\Models\SavingsLoan;
use App\Models\SavingsMovement;
use App\Repositories\PeriodRepository;
use App\Repositories\SavingsLoanRepository;
use App\Repositories\SavingsMovementRepository;
use App\Support\Money;
use DomainException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class LoanService
{
    public function __construct(
        private readonly SavingsLoanRepository $loans,
        private readonly SavingsMovementRepository $movements,
        private readonly PeriodRepository $periods,
        private readonly FinanceService $finance,
    ) {}

    /**
     * @return Collection<int, SavingsLoan>
     */
    public function loans(): Collection
    {
        return $this->loans->all();
    }

    /**
     * @return Collection<int, SavingsLoan>
     */
    public function pendingLoans(): Collection
    {
        return $this->loans->all()
            ->filter(fn (SavingsLoan $loan) => Money::isPositive($this->finance->outstandingForLoan($loan)))
            ->values();
    }

    public function outstanding(SavingsLoan $loan): string
    {
        return $this->finance->outstandingForLoan($loan);
    }

    public function loanableAmount(): string
    {
        return $this->finance->loanableAmount();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Period $originPeriod, array $data): SavingsLoan
    {
        if ($originPeriod->isClosed()) {
            throw new DomainException('El período está cerrado y no admite modificaciones.');
        }

        $amount = Money::normalize($data['amount']);

        if (! Money::isPositive($amount)) {
            throw new DomainException('El importe del préstamo debe ser mayor que cero.');
        }

        if (Money::compare($amount, $this->finance->loanableAmount()) > 0) {
            throw new DomainException(
                'El préstamo supera el saldo disponible para prestar ('.Money::format($this->finance->loanableAmount()).').'
            );
        }

        $duePeriod = $this->nextPeriod($originPeriod, true);

        return DB::transaction(function () use ($originPeriod, $duePeriod, $amount, $data) {
            $loan = $this->loans->create([
                'origin_period_id' => $originPeriod->id,
                'due_period_id' => $duePeriod->id,
                'amount' => $amount,
                'description' => $data['description'],
            ]);

            $this->movements->create([
                'period_id' => $originPeriod->id,
                'savings_loan_id' => $loan->id,
                'type' => SavingsMovementType::Withdrawal,
                'amount' => $amount,
                'description' => 'Préstamo: '.$data['description'],
                'movement_date' => $data['movement_date'] ?? now()->toDateString(),
            ]);

            return $loan;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function repay(SavingsLoan $loan, Period $period, array $data): SavingsMovement
    {
        if ($period->isClosed()) {
            throw new DomainException('El período está cerrado y no admite modificaciones.');
        }

        $amount = Money::normalize($data['amount']);
        $outstanding = $this->finance->outstandingForLoan($loan);

        if (! Money::isPositive($amount)) {
            throw new DomainException('La reposición debe ser mayor que cero.');
        }

        if (Money::compare($amount, $outstanding) > 0) {
            throw new DomainException(
                'La reposición supera el saldo pendiente ('.Money::format($outstanding).').'
            );
        }

        return DB::transaction(fn () => $this->movements->create([
            'period_id' => $period->id,
            'savings_loan_id' => $loan->id,
            'type' => SavingsMovementType::Repayment,
            'amount' => $amount,
            'description' => $data['description'] ?? 'Reposición de préstamo: '.$loan->description,
            'movement_date' => $data['movement_date'] ?? now()->toDateString(),
        ]));
    }

    public function nextPeriod(Period $period, bool $create = false): ?Period
    {
        $date = Carbon::createFromDate($period->year, $period->month, 1)->addMonth();

        if ($create) {
            return $this->periods->firstOrCreate($date->year, $date->month);
        }

        return $this->periods->findByYearMonth($date->year, $date->month);
    }
}
