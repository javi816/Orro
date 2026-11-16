<?php

namespace App\NativeComponents;

use App\Enums\ExpenseStatus;
use App\Models\Expense;
use App\NativeComponents\Concerns\SelectsPeriod;
use App\Services\ExpenseService;
use App\Services\FinanceService;
use App\Services\PeriodService;
use DomainException;
use Illuminate\View\View;
use InvalidArgumentException;
use Native\Mobile\Edge\NativeComponent;

class ExpensesScreen extends NativeComponent
{
    use SelectsPeriod;

    public string $description = '';

    public string $amount = '';

    public string $expenseDate = '';

    public string $paymentNumber = '1';

    public string $statusLabel = 'Pagado';

    public string $error = '';

    public string $success = '';

    public function onBackPressed(): void
    {
        $this->replace('/');
    }

    public function mount(): void
    {
        $this->bootPeriod();
        $this->expenseDate = now()->toDateString();
    }

    public function chooseStatus(string $label): void
    {
        $this->statusLabel = $label === ExpenseStatus::Pending->label()
            ? ExpenseStatus::Pending->label()
            : ExpenseStatus::Paid->label();
    }

    public function registerVariable(?string $submitted = null): void
    {
        $this->error = '';
        $this->success = '';
        $amount = trim($submitted ?? $this->amount);
        $description = trim($this->description);

        if ($description === '') {
            $this->error = 'Escribe una descripción para el gasto.';

            return;
        }

        try {
            $period = app(PeriodService::class)->refreshStatus($this->bootPeriod());
            app(ExpenseService::class)->registerVariable($period, [
                'description' => $description,
                'amount' => $amount,
                'expense_date' => $this->expenseDate !== '' ? $this->expenseDate : now()->toDateString(),
                'payment_number' => max(1, (int) $this->paymentNumber),
                'status' => $this->statusLabel === ExpenseStatus::Pending->label()
                    ? ExpenseStatus::Pending
                    : ExpenseStatus::Paid,
            ]);
        } catch (DomainException|InvalidArgumentException $exception) {
            $this->error = $exception->getMessage();

            return;
        }

        $this->description = '';
        $this->amount = '';
        $this->success = 'Gasto variable registrado.';
    }

    public function markPaid(int $expenseId): void
    {
        $this->error = '';
        $this->success = '';

        try {
            app(ExpenseService::class)->markPaid(Expense::query()->findOrFail($expenseId));
        } catch (DomainException $exception) {
            $this->error = $exception->getMessage();

            return;
        }

        $this->success = 'Gasto marcado como pagado.';
    }

    public function deleteExpense(int $expenseId): void
    {
        $this->error = '';
        $this->success = '';

        try {
            app(ExpenseService::class)->delete(Expense::query()->findOrFail($expenseId));
        } catch (DomainException $exception) {
            $this->error = $exception->getMessage();

            return;
        }

        $this->success = 'Gasto eliminado.';
    }

    public function generateFixed(): void
    {
        $this->error = '';
        $this->success = '';

        $period = app(PeriodService::class)->refreshStatus($this->bootPeriod());
        $created = app(ExpenseService::class)->generateFixed($period);

        $this->success = $created === 0
            ? 'No había gastos fijos pendientes por generar.'
            : $created.' gasto(s) fijo(s) generado(s).';
    }

    public function render(): View
    {
        $period = app(PeriodService::class)->refreshStatus($this->bootPeriod());
        $expenses = app(ExpenseService::class)->forPeriod($period);

        return view('native.expenses', [
            'period' => $period,
            'periodLabels' => $this->periodLabels(),
            'fixedExpenses' => $expenses->filter(fn (Expense $expense) => $expense->type->value === 'fixed'),
            'variableExpenses' => $expenses->filter(fn (Expense $expense) => $expense->type->value === 'variable'),
            'summary' => app(FinanceService::class)->periodSummary($period),
            'statusLabels' => [ExpenseStatus::Paid->label(), ExpenseStatus::Pending->label()],
        ]);
    }
}
