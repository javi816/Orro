<?php

namespace App\Http\Controllers;

use App\Services\FinanceService;
use App\Services\IncomeService;
use App\Services\PeriodService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(
        Request $request,
        PeriodService $periods,
        FinanceService $finance,
        IncomeService $incomes,
    ): View {
        $period = $periods->refreshStatus($periods->resolve($request->integer('period') ?: null));
        $readiness = $periods->readiness($period);

        return view('dashboard', [
            'period' => $period,
            'periods' => $periods->periods(),
            'summary' => $readiness['summary'],
            'readiness' => $readiness,
            'nextRepayment' => $finance->nextRepayment(),
            'pendingIncomes' => $incomes->forPeriod($period)
                ->filter(fn ($income) => $income->status->value === 'pending'),
        ]);
    }
}
