<?php

namespace App\Http\Controllers;

use App\Services\FinanceService;
use App\Services\PeriodService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BudgetController extends Controller
{
    public function index(Request $request, PeriodService $periods, FinanceService $finance): View
    {
        $period = $periods->refreshStatus($periods->resolve($request->integer('period') ?: null));

        return view('budget.index', [
            'period' => $period,
            'periods' => $periods->periods(),
            'summary' => $finance->periodSummary($period),
        ]);
    }

    public function update(Request $request, PeriodService $periods): RedirectResponse
    {
        $data = $request->validate([
            'period_id' => ['required', 'integer', 'exists:periods,id'],
            'variable_budget' => ['required', 'regex:/^\d{1,10}([.,]\d{1,2})?$/'],
        ]);

        $period = $periods->resolve($data['period_id']);
        $periods->updateVariableBudget($period, $data['variable_budget']);

        return redirect()
            ->route('budget.index', ['period' => $period->id])
            ->with('success', 'Presupuesto variable actualizado.');
    }
}
