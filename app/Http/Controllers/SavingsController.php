<?php

namespace App\Http\Controllers;

use App\Enums\SavingsMovementType;
use App\Services\FinanceService;
use App\Services\PeriodService;
use App\Services\SavingsService;
use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SavingsController extends Controller
{
    public function index(
        Request $request,
        PeriodService $periods,
        SavingsService $savings,
        FinanceService $finance,
        SettingsService $settings,
    ): View {
        $period = $periods->refreshStatus($periods->resolve($request->integer('period') ?: null));

        return view('savings.index', [
            'period' => $period,
            'periods' => $periods->periods(),
            'movements' => $savings->movements(),
            'balance' => $savings->balance(),
            'hasOpeningBalance' => $savings->hasOpeningBalance(),
            'summary' => $finance->periodSummary($period),
            'settings' => $settings->settings(),
        ]);
    }

    public function store(Request $request, PeriodService $periods, SavingsService $savings): RedirectResponse
    {
        $data = $request->validate([
            'period_id' => ['required', 'integer', 'exists:periods,id'],
            'type' => ['required', Rule::in([
                SavingsMovementType::OpeningBalance->value,
                SavingsMovementType::Deposit->value,
                SavingsMovementType::Withdrawal->value,
            ])],
            'amount' => ['required', 'regex:/^\d{1,10}([.,]\d{1,2})?$/'],
            'movement_date' => ['nullable', 'date'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $period = $periods->resolve($data['period_id']);
        $savings->register($period, $data);

        return redirect()
            ->route('savings.index', ['period' => $period->id])
            ->with('success', 'Movimiento de ahorro registrado.');
    }
}
