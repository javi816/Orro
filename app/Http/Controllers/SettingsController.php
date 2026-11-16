<?php

namespace App\Http\Controllers;

use App\Models\ExpenseTemplate;
use App\Models\IncomeSource;
use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function edit(SettingsService $settings): View
    {
        return view('settings.index', [
            'settings' => $settings->settings(),
            'incomeSources' => $settings->incomeSources(),
            'expenseTemplates' => $settings->expenseTemplates(),
        ]);
    }

    public function update(Request $request, SettingsService $settings): RedirectResponse
    {
        $data = $request->validate([
            'monthly_savings_goal' => ['required', 'regex:/^\d{1,10}([.,]\d{1,2})?$/'],
            'minimum_savings_balance' => ['required', 'regex:/^\d{1,10}([.,]\d{1,2})?$/'],
        ]);

        $settings->updateSettings($data);

        return back()->with('success', 'Configuración general actualizada.');
    }

    public function storeIncomeSource(Request $request, SettingsService $settings): RedirectResponse
    {
        $settings->saveIncomeSource(null, $this->validateIncomeSource($request));

        return back()->with('success', 'Fuente de ingreso creada.');
    }

    public function updateIncomeSource(Request $request, IncomeSource $source, SettingsService $settings): RedirectResponse
    {
        $settings->saveIncomeSource($source, $this->validateIncomeSource($request));

        return back()->with('success', 'Fuente de ingreso actualizada. Los períodos ya generados no cambian.');
    }

    public function toggleIncomeSource(IncomeSource $source, SettingsService $settings): RedirectResponse
    {
        $settings->toggleIncomeSource($source);

        return back()->with('success', $source->refresh()->active
            ? 'Fuente de ingreso activada.'
            : 'Fuente de ingreso desactivada.');
    }

    public function storeExpenseTemplate(Request $request, SettingsService $settings): RedirectResponse
    {
        $settings->saveExpenseTemplate(null, $this->validateExpenseTemplate($request));

        return back()->with('success', 'Gasto fijo creado.');
    }

    public function updateExpenseTemplate(Request $request, ExpenseTemplate $template, SettingsService $settings): RedirectResponse
    {
        $settings->saveExpenseTemplate($template, $this->validateExpenseTemplate($request));

        return back()->with('success', 'Gasto fijo actualizado. Los períodos ya generados no cambian.');
    }

    public function toggleExpenseTemplate(ExpenseTemplate $template, SettingsService $settings): RedirectResponse
    {
        $settings->toggleExpenseTemplate($template);

        return back()->with('success', $template->refresh()->active
            ? 'Gasto fijo activado.'
            : 'Gasto fijo desactivado.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateIncomeSource(Request $request): array
    {
        return $request->validate([
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'regex:/^\d{1,10}([.,]\d{1,2})?$/'],
            'payment_number' => ['required', 'integer', 'min:1', 'max:31'],
            'active' => ['nullable', 'boolean'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateExpenseTemplate(Request $request): array
    {
        return $request->validate([
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'regex:/^\d{1,10}([.,]\d{1,2})?$/'],
            'payment_number' => ['required', 'integer', 'min:1', 'max:31'],
            'active' => ['nullable', 'boolean'],
        ]);
    }
}
