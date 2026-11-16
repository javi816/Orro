<?php

namespace App\Http\Controllers;

use App\Models\SavingsLoan;
use App\Services\FinanceService;
use App\Services\LoanService;
use App\Services\PeriodService;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LoanController extends Controller
{
    public function index(
        Request $request,
        PeriodService $periods,
        LoanService $loans,
        FinanceService $finance,
    ): View {
        $period = $periods->refreshStatus($periods->resolve($request->integer('period') ?: null));

        $loansWithBalance = $loans->loans()->map(fn (SavingsLoan $loan) => [
            'loan' => $loan,
            'outstanding' => $loans->outstanding($loan),
        ]);

        return view('loans.index', [
            'period' => $period,
            'periods' => $periods->periods(),
            'loans' => $loansWithBalance,
            'pendingLoans' => $loansWithBalance->filter(fn (array $item) => Money::isPositive($item['outstanding'])),
            'loanable' => $loans->loanableAmount(),
            'nextRepayment' => $finance->nextRepayment(),
            'nextDuePeriod' => $loans->nextPeriod($period, true),
            'summary' => $finance->periodSummary($period),
        ]);
    }

    public function store(Request $request, PeriodService $periods, LoanService $loans): RedirectResponse
    {
        $data = $request->validate([
            'period_id' => ['required', 'integer', 'exists:periods,id'],
            'amount' => ['required', 'regex:/^\d{1,10}([.,]\d{1,2})?$/'],
            'description' => ['required', 'string', 'max:255'],
        ]);

        $period = $periods->resolve($data['period_id']);
        $loan = $loans->create($period, $data);

        return redirect()
            ->route('loans.index', ['period' => $period->id])
            ->with('success', 'Préstamo registrado. Vence en '.$loan->duePeriod->label().'.');
    }

    public function repay(
        Request $request,
        SavingsLoan $loan,
        PeriodService $periods,
        LoanService $loans,
    ): RedirectResponse {
        $data = $request->validate([
            'period_id' => ['required', 'integer', 'exists:periods,id'],
            'amount' => ['required', 'regex:/^\d{1,10}([.,]\d{1,2})?$/'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $period = $periods->resolve($data['period_id']);
        $loans->repay($loan, $period, $data);

        return redirect()
            ->route('loans.index', ['period' => $period->id])
            ->with('success', 'Reposición registrada.');
    }
}
