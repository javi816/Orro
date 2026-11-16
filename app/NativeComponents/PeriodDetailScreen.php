<?php

namespace App\NativeComponents;

use App\Models\Period;
use App\Services\FinanceService;
use App\Services\PeriodService;
use App\Support\PeriodOptions;
use DomainException;
use Illuminate\View\View;
use Native\Mobile\Edge\NativeComponent;

/**
 * Detalle y cierre de un período.
 *
 * El período llega por route-model binding (`public Period $period`).
 */
class PeriodDetailScreen extends NativeComponent
{
    public Period $period;

    public string $error = '';

    public string $success = '';

    public function onBackPressed(): void
    {
        $this->replace('/periodos');
    }

    public function choosePeriod(string $label): void
    {
        $periods = app(PeriodService::class)->periods();
        $periodId = PeriodOptions::idFromLabel($periods, $label);

        if ($periodId === null || $periodId === $this->period->id) {
            return;
        }

        $this->replace('/periodos/'.$periodId);
    }

    public function close(): void
    {
        $this->error = '';
        $this->success = '';

        try {
            app(PeriodService::class)->close($this->period);
        } catch (DomainException $exception) {
            $this->error = $exception->getMessage();

            return;
        }

        $this->replace('/periodos/'.$this->period->id);
    }

    public function render(): View
    {
        $periods = app(PeriodService::class);
        $period = $this->period->isClosed()
            ? $this->period
            : $periods->refreshStatus($this->period);

        $readiness = $periods->readiness($period);

        return view('native.period-detail', [
            'period' => $period,
            'periodLabels' => PeriodOptions::labels($periods->periods()),
            'periodLabel' => $period->label(),
            'readiness' => $readiness,
            'summary' => $readiness['summary'],
            'closingPreview' => app(FinanceService::class)->closingSummary($period),
        ]);
    }
}
