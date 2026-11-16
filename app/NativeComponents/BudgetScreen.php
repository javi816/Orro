<?php

namespace App\NativeComponents;

use App\NativeComponents\Concerns\SelectsPeriod;
use App\Services\FinanceService;
use App\Services\PeriodService;
use DomainException;
use Illuminate\View\View;
use InvalidArgumentException;
use Native\Mobile\Edge\NativeComponent;

class BudgetScreen extends NativeComponent
{
    use SelectsPeriod;

    public string $variableBudget = '';

    public string $error = '';

    public string $success = '';

    public function onBackPressed(): void
    {
        $this->replace('/mas');
    }

    public function mount(): void
    {
        $period = $this->bootPeriod();
        $this->variableBudget = (string) $period->variable_budget;
    }

    public function save(?string $submitted = null): void
    {
        $this->error = '';
        $this->success = '';

        try {
            $period = app(PeriodService::class)->refreshStatus($this->bootPeriod());
            app(PeriodService::class)->updateVariableBudget($period, trim($submitted ?? $this->variableBudget));
        } catch (DomainException|InvalidArgumentException $exception) {
            $this->error = $exception->getMessage();

            return;
        }

        $this->success = 'Presupuesto variable actualizado.';
    }

    public function render(): View
    {
        $period = app(PeriodService::class)->refreshStatus($this->bootPeriod());

        $summary = app(FinanceService::class)->periodSummary($period);
        $budget = (float) $period->variable_budget;
        $spent = (float) $summary['variable_spent'];
        $progress = $budget > 0 ? min(1.0, max(0.0, $spent / $budget)) : 0.0;

        return view('native.budget', [
            'period' => $period,
            'periodLabels' => $this->periodLabels(),
            'summary' => $summary,
            'progress' => $progress,
        ]);
    }
}
