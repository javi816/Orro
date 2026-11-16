<?php

namespace App\Http\Controllers;

use App\Models\Income;
use App\Services\FinanceService;
use App\Services\IncomeService;
use App\Services\PeriodService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class IncomeController extends Controller
{
    public function index(Request $request, PeriodService $periods, IncomeService $incomes, FinanceService $finance): View
    {
        $period = $periods->refreshStatus($periods->resolve($request->integer('period') ?: null));

        return view('incomes.index', [
            'period' => $period,
            'periods' => $periods->periods(),
            'incomes' => $incomes->forPeriod($period),
            'summary' => $finance->periodSummary($period),
        ]);
    }

    public function receive(Income $income, IncomeService $incomes): RedirectResponse
    {
        $incomes->markReceived($income);

        return back()->with('success', 'Ingreso marcado como recibido.');
    }

    public function generate(Request $request, PeriodService $periods, IncomeService $incomes): RedirectResponse
    {
        $data = $request->validate([
            'period_id' => ['required', 'integer', 'exists:periods,id'],
        ]);

        $period = $periods->resolve($data['period_id']);
        $created = $incomes->generateExpected($period);

        return redirect()
            ->route('incomes.index', ['period' => $period->id])
            ->with('success', $created === 0
                ? 'No había ingresos pendientes por generar.'
                : $created.' ingreso(s) esperado(s) generado(s).');
    }
}
