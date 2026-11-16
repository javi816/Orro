<?php

namespace App\NativeComponents;

use App\Models\SavingsGoal;
use App\Services\SettingsService;
use App\Support\Money;
use DomainException;
use Illuminate\View\View;
use InvalidArgumentException;
use Native\Mobile\Edge\NativeComponent;

class SettingsScreen extends NativeComponent
{
    public string $monthlySavingsGoal = '0.00';

    public string $minimumSavingsBalance = '0.00';

    public string $sourceDescription = '';

    public string $sourceAmount = '';

    public string $sourcePaymentNumber = '1';

    public string $sourceFrequency = 'monthly';

    public bool $sourceActive = true;

    public ?int $editingSourceId = null;

    public string $templateDescription = '';

    public string $templateAmount = '';

    public string $templatePaymentNumber = '1';

    public string $templateFrequency = 'monthly';

    public bool $templateActive = true;

    public ?int $editingTemplateId = null;

    public string $goalName = '';

    public string $goalMonthlyGoal = '0.00';

    public string $goalFinalGoal = '0.00';

    public string $error = '';

    public string $success = '';

    public function onBackPressed(): void
    {
        $this->replace('/mas');
    }

    public function mount(): void
    {
        $settings = app(SettingsService::class)->settings();

        $this->monthlySavingsGoal = (string) $settings->monthly_savings_goal;
        $this->minimumSavingsBalance = (string) $settings->minimum_savings_balance;
    }

    public function saveSettings(): void
    {
        $this->error = '';
        $this->success = '';

        try {
            app(SettingsService::class)->updateSettings([
                'monthly_savings_goal' => $this->monthlySavingsGoal,
                'minimum_savings_balance' => $this->minimumSavingsBalance,
            ]);
        } catch (DomainException|InvalidArgumentException $exception) {
            $this->error = $exception->getMessage();

            return;
        }

        $this->success = 'Configuración general actualizada.';
    }

    public function saveSource(): void
    {
        $this->error = '';
        $this->success = '';
        $description = trim($this->sourceDescription);

        if ($description === '') {
            $this->error = 'Escribe una descripción para la fuente de ingreso.';

            return;
        }

        $service = app(SettingsService::class);
        $source = $this->editingSourceId === null
            ? null
            : $service->incomeSources()->firstWhere('id', $this->editingSourceId);

        try {
            $service->saveIncomeSource($source, [
                'description' => $description,
                'amount' => $this->sourceAmount,
                'payment_number' => $this->sourcePaymentNumber,
                'frequency' => $this->sourceFrequency,
                'active' => $this->sourceActive,
            ]);
        } catch (DomainException|InvalidArgumentException $exception) {
            $this->error = $exception->getMessage();

            return;
        }

        $this->resetSourceForm();
        $this->success = $source === null
            ? 'Fuente de ingreso creada.'
            : 'Fuente de ingreso actualizada.';
    }

    public function editSource(int $sourceId): void
    {
        $source = app(SettingsService::class)->incomeSources()->firstWhere('id', $sourceId);

        if ($source === null) {
            return;
        }

        $this->editingSourceId = $source->id;
        $this->sourceDescription = $source->description;
        $this->sourceAmount = (string) $source->amount;
        $this->sourcePaymentNumber = (string) $source->payment_number;
        $this->sourceFrequency = $source->frequency ?? 'monthly';
        $this->sourceActive = (bool) $source->active;
    }

    public function cancelSourceEdit(): void
    {
        $this->resetSourceForm();
    }

    public function toggleSource(int $sourceId, bool $active): void
    {
        $this->error = '';

        $source = app(SettingsService::class)->incomeSources()->firstWhere('id', $sourceId);

        if ($source === null) {
            return;
        }

        app(SettingsService::class)->saveIncomeSource($source, [
            'description' => $source->description,
            'amount' => (string) $source->amount,
            'payment_number' => (string) $source->payment_number,
            'active' => $active,
        ]);
    }

    public function saveTemplate(): void
    {
        $this->error = '';
        $this->success = '';
        $description = trim($this->templateDescription);

        if ($description === '') {
            $this->error = 'Escribe una descripción para el gasto fijo.';

            return;
        }

        $service = app(SettingsService::class);
        $template = $this->editingTemplateId === null
            ? null
            : $service->expenseTemplates()->firstWhere('id', $this->editingTemplateId);

        try {
            $service->saveExpenseTemplate($template, [
                'description' => $description,
                'amount' => $this->templateAmount,
                'payment_number' => $this->templatePaymentNumber,
                'frequency' => $this->templateFrequency,
                'active' => $this->templateActive,
            ]);
        } catch (DomainException|InvalidArgumentException $exception) {
            $this->error = $exception->getMessage();

            return;
        }

        $this->resetTemplateForm();
        $this->success = $template === null
            ? 'Gasto fijo creado.'
            : 'Gasto fijo actualizado.';
    }

    public function editTemplate(int $templateId): void
    {
        $template = app(SettingsService::class)->expenseTemplates()->firstWhere('id', $templateId);

        if ($template === null) {
            return;
        }

        $this->editingTemplateId = $template->id;
        $this->templateDescription = $template->description;
        $this->templateAmount = (string) $template->amount;
        $this->templatePaymentNumber = (string) $template->payment_number;
        $this->templateFrequency = $template->frequency ?? 'monthly';
        $this->templateActive = (bool) $template->active;
    }

    public function cancelTemplateEdit(): void
    {
        $this->resetTemplateForm();
    }

    public function toggleTemplate(int $templateId, bool $active): void
    {
        $this->error = '';

        $template = app(SettingsService::class)->expenseTemplates()->firstWhere('id', $templateId);

        if ($template === null) {
            return;
        }

        app(SettingsService::class)->saveExpenseTemplate($template, [
            'description' => $template->description,
            'amount' => (string) $template->amount,
            'payment_number' => (string) $template->payment_number,
            'active' => $active,
        ]);
    }

    public function saveGoal(): void
    {
        $this->error = '';
        $this->success = '';

        $name = trim($this->goalName);

        if ($name === '') {
            $this->error = 'Escribe un nombre para la meta.';

            return;
        }

        SavingsGoal::query()->create([
            'name' => $name,
            'monthly_goal' => Money::normalize($this->goalMonthlyGoal),
            'final_goal' => Money::normalize($this->goalFinalGoal),
            'active' => true,
        ]);

        $this->goalName = '';
        $this->goalMonthlyGoal = '0.00';
        $this->goalFinalGoal = '0.00';
        $this->success = 'Meta de ahorro creada.';
    }

    public function deleteGoal(int $goalId): void
    {
        $this->error = '';
        $this->success = '';

        $goal = SavingsGoal::query()->find($goalId);

        if ($goal === null) {
            return;
        }

        $goal->delete();
        $this->success = 'Meta eliminada.';
    }

    public function render(): View
    {
        $settings = app(SettingsService::class);

        return view('native.settings', [
            'settings' => $settings->settings(),
            'incomeSources' => $settings->incomeSources(),
            'expenseTemplates' => $settings->expenseTemplates(),
            'savingsGoals' => SavingsGoal::query()->orderBy('name')->get(),
        ]);
    }

    public function chooseSourceFrequency(string $label): void
    {
        $this->sourceFrequency = $label === 'Quincenal' ? 'biweekly' : 'monthly';
    }

    public function chooseTemplateFrequency(string $label): void
    {
        $this->templateFrequency = $label === 'Quincenal' ? 'biweekly' : 'monthly';
    }

    private function resetSourceForm(): void
    {
        $this->sourceDescription = '';
        $this->sourceAmount = '';
        $this->sourcePaymentNumber = '1';
        $this->sourceFrequency = 'monthly';
        $this->sourceActive = true;
    }

    private function resetTemplateForm(): void
    {
        $this->editingTemplateId = null;
        $this->templateDescription = '';
        $this->templateAmount = '';
        $this->templatePaymentNumber = '1';
        $this->templateFrequency = 'monthly';
        $this->templateActive = true;
    }
}
