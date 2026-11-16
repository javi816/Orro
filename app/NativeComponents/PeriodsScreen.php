<?php

namespace App\NativeComponents;

use App\Models\Period;
use App\Services\FinanceService;
use App\Services\PeriodService;
use Illuminate\View\View;
use Native\Mobile\Edge\NativeComponent;

class PeriodsScreen extends NativeComponent
{
    public function onBackPressed(): void
    {
        $this->replace('/mas');
    }

    public function open(int $periodId): void
    {
        $this->navigate('/periodos/'.$periodId);
    }

    public function render(): View
    {
        $periods = app(PeriodService::class);

        foreach ($periods->periods() as $period) {
            if (! $period->isClosed()) {
                $periods->refreshStatus($period);
            }
        }

        $allPeriods = $periods->periods();
        $finance = app(FinanceService::class);

        $summaries = $allPeriods->mapWithKeys(
            fn (Period $period) => [$period->id => $period->isClosed() && $period->closing_summary
                ? $period->closing_summary
                : $finance->periodSummary($period)]
        );

        return view('native.periods', [
            'periods' => $allPeriods,
            'summaries' => $summaries,
        ]);
    }
}
