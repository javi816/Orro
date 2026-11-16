<?php

namespace App\NativeComponents;

use App\NativeComponents\Concerns\SelectsPeriod;
use App\Services\FinanceService;
use App\Services\IncomeService;
use App\Services\PeriodService;
use Illuminate\View\View;
use Native\Mobile\Edge\NativeComponent;

class DashboardScreen extends NativeComponent
{
    use SelectsPeriod;

    public function mount(): void
    {
        $this->bootPeriod();
    }

    public function render(): View
    {
        $periods = app(PeriodService::class);
        $period = $periods->refreshStatus($this->bootPeriod());
        $readiness = $periods->readiness($period);

        return view('native.dashboard', [
            'period' => $period,
            'periodLabels' => $this->periodLabels(),
            'summary' => $readiness['summary'],
            'readiness' => $readiness,
            'nextRepayment' => app(FinanceService::class)->nextRepayment(),
            'pendingIncomes' => app(IncomeService::class)
                ->forPeriod($period)
                ->filter(fn ($income) => $income->status->value === 'pending'),
        ]);
    }
}
