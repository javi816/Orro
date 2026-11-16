<?php

namespace App\NativeComponents;

use App\Models\Income;
use App\NativeComponents\Concerns\SelectsPeriod;
use App\Services\FinanceService;
use App\Services\IncomeService;
use App\Services\PeriodService;
use DomainException;
use Illuminate\View\View;
use Native\Mobile\Edge\NativeComponent;

class IncomesScreen extends NativeComponent
{
    use SelectsPeriod;

    public string $error = '';

    public string $success = '';

    public function onBackPressed(): void
    {
        $this->replace('/mas');
    }

    public function mount(): void
    {
        $this->bootPeriod();
    }

    public function markReceived(int $incomeId): void
    {
        $this->error = '';
        $this->success = '';

        try {
            app(IncomeService::class)->markReceived(Income::query()->findOrFail($incomeId));
        } catch (DomainException $exception) {
            $this->error = $exception->getMessage();

            return;
        }

        $this->success = 'Ingreso marcado como recibido.';
    }

    public function generate(): void
    {
        $this->error = '';
        $this->success = '';

        $period = app(PeriodService::class)->refreshStatus($this->bootPeriod());
        $created = app(IncomeService::class)->generateExpected($period);

        $this->success = $created === 0
            ? 'No había ingresos pendientes por generar.'
            : $created.' ingreso(s) esperado(s) generado(s).';
    }

    public function render(): View
    {
        $period = app(PeriodService::class)->refreshStatus($this->bootPeriod());

        return view('native.incomes', [
            'period' => $period,
            'periodLabels' => $this->periodLabels(),
            'incomes' => app(IncomeService::class)->forPeriod($period),
            'summary' => app(FinanceService::class)->periodSummary($period),
        ]);
    }
}
