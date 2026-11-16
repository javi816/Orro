<?php

namespace App\Http\Controllers;

use App\Enums\ExpenseStatus;
use App\Models\Expense;
use App\Services\ExpenseService;
use App\Services\FinanceService;
use App\Services\PeriodService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    public function index(Request $request, PeriodService $periods, ExpenseService $expenses, FinanceService $finance): View
    {
        $period = $periods->refreshStatus($periods->resolve($request->integer('period') ?: null));
        $periodExpenses = $expenses->forPeriod($period);

        return view('expenses.index', [
            'period' => $period,
            'periods' => $periods->periods(),
            'fixedExpenses' => $periodExpenses->filter(fn ($expense) => $expense->type->value === 'fixed'),
            'variableExpenses' => $periodExpenses->filter(fn ($expense) => $expense->type->value === 'variable'),
            'summary' => $finance->periodSummary($period),
        ]);
    }

    public function store(Request $request, PeriodService $periods, ExpenseService $expenses): RedirectResponse
    {
        $data = $request->validate([
            'period_id' => ['required', 'integer', 'exists:periods,id'],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'regex:/^\d{1,10}([.,]\d{1,2})?$/'],
            'expense_date' => ['nullable', 'date'],
            'payment_number' => ['required', 'integer', 'min:1', 'max:31'],
            'status' => ['required', Rule::enum(ExpenseStatus::class)],
        ]);

        $period = $periods->resolve($data['period_id']);
        $expenses->registerVariable($period, $data);

        return redirect()
            ->route('expenses.index', ['period' => $period->id])
            ->with('success', 'Gasto variable registrado.');
    }

    public function pay(Expense $expense, ExpenseService $expenses): RedirectResponse
    {
        $expenses->markPaid($expense);

        return back()->with('success', 'Gasto marcado como pagado.');
    }

    public function generate(Request $request, PeriodService $periods, ExpenseService $expenses): RedirectResponse
    {
        $data = $request->validate([
            'period_id' => ['required', 'integer', 'exists:periods,id'],
        ]);

        $period = $periods->resolve($data['period_id']);
        $created = $expenses->generateFixed($period);

        return redirect()
            ->route('expenses.index', ['period' => $period->id])
            ->with('success', $created === 0
                ? 'No había gastos fijos pendientes por generar.'
                : $created.' gasto(s) fijo(s) generado(s).');
    }
}
