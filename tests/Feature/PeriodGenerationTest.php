<?php

namespace Tests\Feature;

use App\Enums\ExpenseStatus;
use App\Enums\ExpenseType;
use App\Enums\IncomeStatus;
use App\Models\Expense;
use App\Models\ExpenseTemplate;
use App\Models\Income;
use App\Models\IncomeSource;
use App\Models\Period;
use App\Services\PeriodService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PeriodGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_period_generation_is_idempotent(): void
    {
        $source = IncomeSource::factory()->create([
            'description' => 'Salario',
            'amount' => '1000.00',
            'payment_number' => 1,
        ]);

        $template = ExpenseTemplate::factory()->create([
            'description' => 'Arriendo',
            'amount' => '400.00',
            'payment_number' => 2,
        ]);

        $periods = $this->app->make(PeriodService::class);

        $period = $periods->ensure(2030, 5);
        $again = $periods->ensure(2030, 5);

        $this->assertSame($period->id, $again->id);
        $this->assertSame(1, Period::query()->count());
        $this->assertSame(1, Income::query()->count());
        $this->assertSame(1, Expense::query()->count());

        $income = Income::query()->firstOrFail();
        $this->assertSame($source->id, $income->income_source_id);
        $this->assertSame('Salario', $income->description);
        $this->assertSame('1000.00', $income->amount);
        $this->assertSame(IncomeStatus::Pending, $income->status);

        $expense = Expense::query()->firstOrFail();
        $this->assertSame($template->id, $expense->expense_template_id);
        $this->assertSame('Arriendo', $expense->description);
        $this->assertSame('400.00', $expense->planned_amount);
        $this->assertSame(ExpenseType::Fixed, $expense->type);
        $this->assertSame(ExpenseStatus::Pending, $expense->status);
    }

    public function test_generated_periods_keep_their_values_when_templates_change(): void
    {
        $source = IncomeSource::factory()->create([
            'description' => 'Salario',
            'amount' => '1000.00',
        ]);

        $template = ExpenseTemplate::factory()->create([
            'description' => 'Arriendo',
            'amount' => '400.00',
        ]);

        $periods = $this->app->make(PeriodService::class);
        $periods->ensure(2030, 5);

        $source->update(['amount' => '1200.00']);
        $template->update(['amount' => '500.00']);

        $periods->ensure(2030, 5);

        $this->assertSame('1000.00', Income::query()->firstOrFail()->amount);
        $this->assertSame('400.00', Expense::query()->firstOrFail()->planned_amount);

        $periods->ensure(2030, 6);

        $nextIncome = Income::query()->where('period_id', '!=', Period::query()->firstOrFail()->id)->firstOrFail();
        $nextExpense = Expense::query()->where('period_id', '!=', Period::query()->firstOrFail()->id)->firstOrFail();

        $this->assertSame('1200.00', $nextIncome->amount);
        $this->assertSame('500.00', $nextExpense->planned_amount);
    }

    public function test_inactive_sources_and_templates_are_not_generated(): void
    {
        IncomeSource::factory()->create(['description' => 'Antiguo', 'active' => false]);
        ExpenseTemplate::factory()->create(['description' => 'Antiguo', 'active' => false]);

        $period = $this->app->make(PeriodService::class)->ensure(2030, 7);

        $this->assertSame(0, $period->incomes()->count());
        $this->assertSame(0, $period->expenses()->count());
    }
}
