<?php

namespace App\Services;

use App\Models\ExpenseTemplate;
use App\Models\IncomeSource;
use App\Models\Setting;
use App\Repositories\ExpenseTemplateRepository;
use App\Repositories\IncomeSourceRepository;
use App\Repositories\SettingsRepository;
use App\Support\Money;
use Illuminate\Database\Eloquent\Collection;

class SettingsService
{
    public function __construct(
        private readonly SettingsRepository $settings,
        private readonly IncomeSourceRepository $incomeSources,
        private readonly ExpenseTemplateRepository $expenseTemplates,
    ) {}

    public function settings(): Setting
    {
        return $this->settings->current();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateSettings(array $data): Setting
    {
        return $this->settings->update($this->settings->current(), [
            'monthly_savings_goal' => Money::normalize($data['monthly_savings_goal']),
            'minimum_savings_balance' => Money::normalize($data['minimum_savings_balance']),
        ]);
    }

    /**
     * @return Collection<int, IncomeSource>
     */
    public function incomeSources(): Collection
    {
        return $this->incomeSources->all();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function saveIncomeSource(?IncomeSource $source, array $data): IncomeSource
    {
        $attributes = [
            'description' => $data['description'],
            'amount' => Money::normalize($data['amount']),
            'payment_number' => (int) $data['payment_number'],
            'frequency' => $data['frequency'] ?? 'monthly',
            'active' => (bool) ($data['active'] ?? false),
        ];

        return $source
            ? $this->incomeSources->update($source, $attributes)
            : $this->incomeSources->create($attributes);
    }

    public function toggleIncomeSource(IncomeSource $source): IncomeSource
    {
        return $this->incomeSources->update($source, ['active' => ! $source->active]);
    }

    /**
     * @return Collection<int, ExpenseTemplate>
     */
    public function expenseTemplates(): Collection
    {
        return $this->expenseTemplates->all();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function saveExpenseTemplate(?ExpenseTemplate $template, array $data): ExpenseTemplate
    {
        $attributes = [
            'description' => $data['description'],
            'amount' => Money::normalize($data['amount']),
            'payment_number' => (int) $data['payment_number'],
            'frequency' => $data['frequency'] ?? 'monthly',
            'active' => (bool) ($data['active'] ?? false),
        ];

        return $template
            ? $this->expenseTemplates->update($template, $attributes)
            : $this->expenseTemplates->create($attributes);
    }

    public function toggleExpenseTemplate(ExpenseTemplate $template): ExpenseTemplate
    {
        return $this->expenseTemplates->update($template, ['active' => ! $template->active]);
    }
}
