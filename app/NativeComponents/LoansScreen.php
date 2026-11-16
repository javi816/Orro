<?php

namespace App\NativeComponents;

use App\Models\SavingsLoan;
use App\NativeComponents\Concerns\SelectsPeriod;
use App\Services\FinanceService;
use App\Services\LoanService;
use App\Services\PeriodService;
use App\Support\Money;
use DomainException;
use Illuminate\View\View;
use InvalidArgumentException;
use Native\Mobile\Edge\NativeComponent;

class LoansScreen extends NativeComponent
{
    use SelectsPeriod;

    public string $amount = '';

    public string $description = '';

    public string $repayAmount = '';

    public string $error = '';

    public string $success = '';

    public function onBackPressed(): void
    {
        $this->replace('/');
    }

    public function mount(): void
    {
        $this->bootPeriod();
    }

    public function create(?string $submitted = null): void
    {
        $this->error = '';
        $this->success = '';
        $description = trim($this->description);

        if ($description === '') {
            $this->error = 'Escribe una descripción para el préstamo.';

            return;
        }

        try {
            $period = app(PeriodService::class)->refreshStatus($this->bootPeriod());
            $loan = app(LoanService::class)->create($period, [
                'amount' => trim($submitted ?? $this->amount),
                'description' => $description,
            ]);
        } catch (DomainException|InvalidArgumentException $exception) {
            $this->error = $exception->getMessage();

            return;
        }

        $this->amount = '';
        $this->description = '';
        $this->success = 'Préstamo registrado. Vence en '.$loan->duePeriod->label().'.';
    }

    public function repay(int $loanId, ?string $submitted = null): void
    {
        $this->error = '';
        $this->success = '';

        $loan = SavingsLoan::query()->findOrFail($loanId);
        $amount = trim($submitted ?? $this->repayAmount);

        if ($amount === '') {
            $amount = app(LoanService::class)->outstanding($loan);
        }

        try {
            $period = app(PeriodService::class)->refreshStatus($this->bootPeriod());
            app(LoanService::class)->repay($loan, $period, ['amount' => $amount]);
        } catch (DomainException|InvalidArgumentException $exception) {
            $this->error = $exception->getMessage();

            return;
        }

        $this->repayAmount = '';
        $this->success = 'Reposición registrada.';
    }

    public function render(): View
    {
        $period = app(PeriodService::class)->refreshStatus($this->bootPeriod());
        $loans = app(LoanService::class);
        $loansWithBalance = $loans->loans()->map(fn (SavingsLoan $loan) => [
            'loan' => $loan,
            'outstanding' => $loans->outstanding($loan),
        ]);

        return view('native.loans', [
            'period' => $period,
            'periodLabels' => $this->periodLabels(),
            'loans' => $loansWithBalance,
            'pendingLoans' => $loansWithBalance->filter(fn (array $item) => Money::isPositive($item['outstanding'])),
            'loanable' => $loans->loanableAmount(),
            'nextDuePeriod' => $loans->nextPeriod($period),
            'summary' => app(FinanceService::class)->periodSummary($period),
        ]);
    }
}
