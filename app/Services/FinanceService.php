<?php

namespace App\Services;

use App\Enums\ExpenseStatus;
use App\Enums\ExpenseType;
use App\Enums\IncomeStatus;
use App\Enums\SavingsMovementType;
use App\Models\Period;
use App\Models\SavingsLoan;
use App\Repositories\ExpenseRepository;
use App\Repositories\IncomeRepository;
use App\Repositories\SavingsLoanRepository;
use App\Repositories\SavingsMovementRepository;
use App\Repositories\SettingsRepository;
use App\Support\Money;

/**
 * Cálculos financieros de la aplicación. Ninguna otra capa debe replicarlos.
 */
class FinanceService
{
    public function __construct(
        private readonly IncomeRepository $incomes,
        private readonly ExpenseRepository $expenses,
        private readonly SavingsMovementRepository $movements,
        private readonly SavingsLoanRepository $loans,
        private readonly SettingsRepository $settings,
    ) {}

    public function savingsBalance(): string
    {
        $balance = '0.00';

        foreach ($this->movements->all() as $movement) {
            $balance = $movement->type === SavingsMovementType::Withdrawal
                ? Money::sub($balance, (string) $movement->amount)
                : Money::add($balance, (string) $movement->amount);
        }

        return $balance;
    }

    public function savingsBalanceForGoal(?int $goalId): string
    {
        $balance = '0.00';

        foreach ($this->movements->forGoal($goalId) as $movement) {
            $balance = $movement->type === SavingsMovementType::Withdrawal
                ? Money::sub($balance, (string) $movement->amount)
                : Money::add($balance, (string) $movement->amount);
        }

        return $balance;
    }

    public function loanableAmount(): string
    {
        $settings = $this->settings->current();

        return Money::max(
            Money::sub($this->savingsBalance(), (string) $settings->minimum_savings_balance),
            '0.00'
        );
    }

    public function outstandingForLoan(SavingsLoan $loan): string
    {
        $repayments = $this->movements->forLoan($loan->id)
            ->filter(fn ($movement) => $movement->type === SavingsMovementType::Repayment);

        return Money::max(
            Money::sub((string) $loan->amount, Money::total($repayments->pluck('amount'))),
            '0.00'
        );
    }

    public function loansOutstandingTotal(): string
    {
        $total = '0.00';

        foreach ($this->loans->all() as $loan) {
            $total = Money::add($total, $this->outstandingForLoan($loan));
        }

        return $total;
    }

    public function overdueRepayments(Period $period): string
    {
        $total = '0.00';

        foreach ($this->loans->all() as $loan) {
            if (! $this->isPeriodOnOrBefore($loan->duePeriod, $period)) {
                continue;
            }

            $total = Money::add($total, $this->outstandingForLoan($loan));
        }

        return $total;
    }

    /**
     * Próxima obligación de reposición pendiente, ordenada por vencimiento.
     *
     * @return array{loan: SavingsLoan, outstanding: string}|null
     */
    public function nextRepayment(): ?array
    {
        $pending = $this->loans->all()
            ->map(fn (SavingsLoan $loan) => [
                'loan' => $loan,
                'outstanding' => $this->outstandingForLoan($loan),
            ])
            ->filter(fn (array $item) => Money::isPositive($item['outstanding']))
            ->sortBy(fn (array $item) => ($item['loan']->duePeriod->year * 100) + $item['loan']->duePeriod->month)
            ->values();

        return $pending->first();
    }

    /**
     * Resumen financiero completo de un período.
     *
     * @return array<string, mixed>
     */
    public function periodSummary(Period $period): array
    {
        $incomes = $this->incomes->forPeriod($period);
        $expenses = $this->expenses->forPeriod($period);
        $movements = $this->movements->forPeriod($period);
        $loans = $this->loans->originatedIn($period);

        $incomeReceived = Money::total(
            $incomes->filter(fn ($income) => $income->status === IncomeStatus::Received)->pluck('amount')
        );
        $incomePending = Money::total(
            $incomes->filter(fn ($income) => $income->status === IncomeStatus::Pending)->pluck('amount')
        );

        $fixedPaid = '0.00';
        $fixedPending = '0.00';
        $variableSpent = '0.00';
        $variablePending = '0.00';
        $expensesPaid = '0.00';
        $fixedPendingCount = 0;
        $variablePendingCount = 0;

        foreach ($expenses as $expense) {
            $actual = (string) ($expense->actual_amount ?? $expense->planned_amount);
            $planned = (string) $expense->planned_amount;

            if ($expense->type === ExpenseType::Fixed) {
                if ($expense->status === ExpenseStatus::Paid) {
                    $fixedPaid = Money::add($fixedPaid, $actual);
                } else {
                    $fixedPending = Money::add($fixedPending, $planned);
                    $fixedPendingCount++;
                }
            } else {
                $variableSpent = Money::add($variableSpent, $actual);

                if ($expense->status === ExpenseStatus::Pending) {
                    $variablePending = Money::add($variablePending, $planned);
                    $variablePendingCount++;
                }
            }

            if ($expense->status === ExpenseStatus::Paid) {
                $expensesPaid = Money::add($expensesPaid, $actual);
            }
        }

        $deposits = $this->movementsTotal($movements, SavingsMovementType::Deposit);
        $withdrawals = $this->movementsTotal($movements, SavingsMovementType::Withdrawal);
        $repayments = $this->movementsTotal($movements, SavingsMovementType::Repayment);
        $loansReceived = Money::total($loans->pluck('amount'));

        $goal = (string) $this->settings->current()->monthly_savings_goal;
        $savingsMissing = Money::max(Money::sub($goal, $this->savingsBalance()), '0.00');
        $variableRemaining = Money::sub((string) $period->variable_budget, $variableSpent);
        $overdue = $this->overdueRepayments($period);

        $available = Money::sub(
            Money::add($incomeReceived, $loansReceived),
            Money::add($expensesPaid, $deposits, $overdue)
        );

        $committed = Money::add(
            $deposits,
            $overdue
        );

        return [
            'period' => $period,
            'income_received' => $incomeReceived,
            'income_pending' => $incomePending,
            'income_pending_count' => $incomes->filter(fn ($income) => $income->status === IncomeStatus::Pending)->count(),
            'income_expected' => Money::add($incomeReceived, $incomePending),
            'fixed_paid' => $fixedPaid,
            'fixed_pending' => $fixedPending,
            'fixed_pending_count' => $fixedPendingCount,
            'variable_spent' => $variableSpent,
            'variable_pending' => $variablePending,
            'variable_pending_count' => $variablePendingCount,
            'expenses_paid' => $expensesPaid,
            'variable_budget' => (string) $period->variable_budget,
            'variable_remaining' => $variableRemaining,
            'savings_goal' => $goal,
            'savings_deposited' => $deposits,
            'savings_missing' => $savingsMissing,
            'savings_withdrawn' => $withdrawals,
            'savings_repaid' => $repayments,
            'savings_balance' => $this->savingsBalance(),
            'loans_received' => $loansReceived,
            'loans_outstanding' => $this->loansOutstandingTotal(),
            'overdue_repayments' => $overdue,
            'available_money' => $available,
            'committed_money' => $committed,
            'minimum_savings_balance' => (string) $this->settings->current()->minimum_savings_balance,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function closingSummary(Period $period): array
    {
        $summary = $this->periodSummary($period);

        return [
            'income_received' => $summary['income_received'],
            'expenses_paid' => $summary['expenses_paid'],
            'savings_deposited' => $summary['savings_deposited'],
            'savings_balance' => $summary['savings_balance'],
            'loans_originated' => $summary['loans_received'],
            'repayments_made' => $summary['savings_repaid'],
            'pending_debt' => $summary['loans_outstanding'],
            'variable_budget' => $summary['variable_budget'],
            'variable_spent' => $summary['variable_spent'],
            'available_money' => $summary['available_money'],
        ];
    }

    private function movementsTotal(iterable $movements, SavingsMovementType $type): string
    {
        $total = '0.00';

        foreach ($movements as $movement) {
            if ($movement->type === $type) {
                $total = Money::add($total, (string) $movement->amount);
            }
        }

        return $total;
    }

    private function isPeriodOnOrBefore(Period $candidate, Period $reference): bool
    {
        return ($candidate->year * 100 + $candidate->month) <= ($reference->year * 100 + $reference->month);
    }
}
