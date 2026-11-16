<?php

namespace App\Http\Controllers;

use App\Models\Period;
use App\Services\FinanceService;
use App\Services\PeriodService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PeriodController extends Controller
{
    public function index(PeriodService $periods, FinanceService $finance): View
    {
        foreach ($periods->periods() as $period) {
            if (! $period->isClosed()) {
                $periods->refreshStatus($period);
            }
        }

        $allPeriods = $periods->periods();

        $summaries = $allPeriods->mapWithKeys(
            fn (Period $period) => [$period->id => $period->isClosed() && $period->closing_summary
                ? $period->closing_summary
                : $finance->periodSummary($period)]
        );

        return view('periods.index', [
            'periods' => $allPeriods,
            'summaries' => $summaries,
        ]);
    }

    public function show(Period $period, PeriodService $periods, FinanceService $finance): View
    {
        $period = $period->isClosed() ? $period : $periods->refreshStatus($period);
        $readiness = $periods->readiness($period);

        return view('periods.show', [
            'period' => $period,
            'periods' => $periods->periods(),
            'readiness' => $readiness,
            'summary' => $readiness['summary'],
            'closingPreview' => $finance->closingSummary($period),
        ]);
    }

    public function close(Period $period, PeriodService $periods): RedirectResponse
    {
        $closed = $periods->close($period);

        return redirect()
            ->route('periods.show', $closed)
            ->with('success', 'Mes cerrado correctamente. El historial quedó protegido.');
    }
}
