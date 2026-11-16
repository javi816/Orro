<?php

namespace Tests\Feature;

use App\Enums\PeriodStatus;
use App\Models\Expense;
use App\Models\ExpenseTemplate;
use App\Models\Income;
use App\Models\Period;
use App\Models\SavingsMovement;
use App\Services\ExpenseService;
use App\Services\IncomeService;
use App\Services\LoanService;
use App\Services\PeriodService;
use App\Services\SavingsService;
use App\Services\SettingsService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PeriodClosingTest extends TestCase
{
    use RefreshDatabase;

    public function test_ready_period_can_be_closed_and_keeps_its_summary(): void
    {
        $period = $this->readyPeriod();
        $periods = $this->app->make(PeriodService::class);

        $readiness = $periods->readiness($period);

        $this->assertTrue($readiness['can_close']);
        $this->assertSame([], $readiness['warnings']);
        $this->assertSame(PeriodStatus::ReadyToClose, $periods->refreshStatus($period)->status);

        $closed = $periods->close($period->refresh());

        $this->assertSame(PeriodStatus::Closed, $closed->status);
        $this->assertNotNull($closed->closed_at);
        $this->assertIsArray($closed->closing_summary);
        $this->assertSame('1000.00', $closed->closing_summary['income_received']);
        $this->assertSame('600.00', $closed->closing_summary['expenses_paid']);
        $this->assertSame('100.00', $closed->closing_summary['savings_deposited']);
        $this->assertSame('300.00', $closed->closing_summary['available_money']);

        $this->app->make(SettingsService::class)->updateSettings([
            'monthly_savings_goal' => '999.00',
            'minimum_savings_balance' => '0.00',
        ]);

        $this->assertSame(
            '1000.00',
            $closed->refresh()->closing_summary['income_received']
        );
    }

    public function test_period_with_pending_expenses_cannot_be_closed(): void
    {
        $period = $this->readyPeriod();

        Expense::factory()->create([
            'period_id' => $period->id,
            'description' => 'Pendiente',
            'planned_amount' => '50.00',
        ]);

        $periods = $this->app->make(PeriodService::class);

        $this->assertFalse($periods->readiness($period->refresh())['can_close']);
        $this->assertSame(PeriodStatus::Open, $periods->refreshStatus($period->refresh())->status);

        $this->assertDomainException(fn () => $periods->close($period->refresh()));

        $this->assertSame(PeriodStatus::Open, $period->refresh()->status);
    }

    public function test_pending_incomes_only_produce_a_warning(): void
    {
        $period = $this->readyPeriod();

        Income::factory()->create([
            'period_id' => $period->id,
            'description' => 'Freelance',
            'amount' => '250.00',
        ]);

        $readiness = $this->app->make(PeriodService::class)->readiness($period->refresh());

        $this->assertTrue($readiness['can_close']);
        $this->assertCount(1, $readiness['warnings']);
        $this->assertStringContainsString('250,00', $readiness['warnings'][0]);
    }

    public function test_closed_period_rejects_business_operations(): void
    {
        $period = $this->readyPeriod();

        $pendingIncome = Income::factory()->create([
            'period_id' => $period->id,
            'description' => 'Freelance',
            'amount' => '250.00',
        ]);

        $periods = $this->app->make(PeriodService::class);
        $closed = $periods->close($period->refresh());

        $this->assertDomainException(fn () => $this->app->make(SavingsService::class)
            ->register($closed, ['type' => 'deposit', 'amount' => '10.00']));

        $this->assertDomainException(fn () => $this->app->make(ExpenseService::class)
            ->registerVariable($closed, ['description' => 'Tarde', 'amount' => '10.00']));

        $this->assertDomainException(fn () => $this->app->make(LoanService::class)
            ->create($closed, ['amount' => '10.00', 'description' => 'Tarde']));

        $this->assertDomainException(fn () => $this->app->make(IncomeService::class)
            ->markReceived($pendingIncome));

        $this->assertDomainException(fn () => $periods->updateVariableBudget($closed, '100.00'));

        $this->assertDomainException(fn () => $periods->close($closed));
    }

    public function test_closed_periods_do_not_generate_new_records(): void
    {
        $period = $this->readyPeriod();
        $closed = $this->app->make(PeriodService::class)->close($period->refresh());

        ExpenseTemplate::factory()->create([
            'description' => 'Nuevo gasto',
            'amount' => '75.00',
        ]);

        $this->app->make(PeriodService::class)->ensure($closed->year, $closed->month);

        $this->assertSame(2, $closed->expenses()->count());
        $this->assertSame(0, $closed->expenses()->where('description', 'Nuevo gasto')->count());
    }

    private function readyPeriod(): Period
    {
        $this->app->make(SettingsService::class)->updateSettings([
            'monthly_savings_goal' => '100.00',
            'minimum_savings_balance' => '0.00',
        ]);

        $period = Period::factory()->create([
            'year' => 2033,
            'month' => 7,
            'variable_budget' => '200.00',
        ]);

        Income::factory()->received()->create([
            'period_id' => $period->id,
            'amount' => '1000.00',
        ]);

        Expense::factory()->paid()->create([
            'period_id' => $period->id,
            'description' => 'Arriendo',
            'planned_amount' => '400.00',
        ]);

        Expense::factory()->variable()->paid()->create([
            'period_id' => $period->id,
            'description' => 'Mercado',
            'planned_amount' => '200.00',
        ]);

        SavingsMovement::factory()->create([
            'period_id' => $period->id,
            'type' => 'deposit',
            'amount' => '100.00',
        ]);

        return $period;
    }

    private function assertDomainException(callable $callback): void
    {
        try {
            $callback();
        } catch (DomainException $exception) {
            $this->assertNotSame('', $exception->getMessage());

            return;
        }

        $this->fail('Se esperaba una DomainException y no fue lanzada.');
    }
}
