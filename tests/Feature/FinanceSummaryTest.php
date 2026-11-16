<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\Income;
use App\Models\Period;
use App\Models\SavingsLoan;
use App\Models\SavingsMovement;
use App\Services\FinanceService;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceSummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_available_money_follows_the_defined_formula(): void
    {
        $this->configureSettings('100.00', '0.00');

        $period = Period::factory()->create([
            'year' => 2031,
            'month' => 3,
            'variable_budget' => '200.00',
        ]);

        Income::factory()->received()->create([
            'period_id' => $period->id,
            'amount' => '1000.00',
        ]);

        Income::factory()->create([
            'period_id' => $period->id,
            'amount' => '300.00',
        ]);

        Expense::factory()->paid()->create([
            'period_id' => $period->id,
            'description' => 'Arriendo',
            'planned_amount' => '300.00',
        ]);

        Expense::factory()->variable()->paid()->create([
            'period_id' => $period->id,
            'description' => 'Mercado',
            'planned_amount' => '100.00',
        ]);

        SavingsMovement::factory()->create([
            'period_id' => $period->id,
            'type' => 'deposit',
            'amount' => '100.00',
        ]);

        $summary = $this->app->make(FinanceService::class)->periodSummary($period);

        $this->assertSame('1000.00', $summary['income_received']);
        $this->assertSame('300.00', $summary['income_pending']);
        $this->assertSame(1, $summary['income_pending_count']);
        $this->assertSame('1300.00', $summary['income_expected']);
        $this->assertSame('400.00', $summary['expenses_paid']);
        $this->assertSame('0.00', $summary['fixed_pending']);
        $this->assertSame('100.00', $summary['variable_spent']);
        $this->assertSame('100.00', $summary['variable_remaining']);
        $this->assertSame('100.00', $summary['savings_deposited']);
        $this->assertSame('0.00', $summary['savings_missing']);
        $this->assertSame('0.00', $summary['overdue_repayments']);
        $this->assertSame('100.00', $summary['savings_balance']);
        $this->assertSame('500.00', $summary['available_money']);
        $this->assertSame('100.00', $summary['committed_money']);
    }

    public function test_overspent_variable_budget_still_holds_back_the_full_budget(): void
    {
        $this->configureSettings('0.00', '0.00');

        $period = Period::factory()->create([
            'year' => 2031,
            'month' => 4,
            'variable_budget' => '200.00',
        ]);

        Income::factory()->received()->create([
            'period_id' => $period->id,
            'amount' => '500.00',
        ]);

        Expense::factory()->create([
            'period_id' => $period->id,
            'planned_amount' => '150.00',
        ]);

        Expense::factory()->variable()->paid()->create([
            'period_id' => $period->id,
            'planned_amount' => '250.00',
        ]);

        $summary = $this->app->make(FinanceService::class)->periodSummary($period);

        $this->assertSame('150.00', $summary['fixed_pending']);
        $this->assertSame('250.00', $summary['variable_spent']);
        $this->assertSame('-50.00', $summary['variable_remaining']);
        $this->assertSame('250.00', $summary['available_money']);
        $this->assertSame('0.00', $summary['committed_money']);
    }

    public function test_overdue_repayments_reduce_available_money(): void
    {
        $this->configureSettings('0.00', '0.00');

        $period = Period::factory()->create(['year' => 2035, 'month' => 6]);
        $origin = Period::factory()->create(['year' => 2035, 'month' => 4]);
        $due = Period::factory()->create(['year' => 2035, 'month' => 5]);

        Income::factory()->received()->create([
            'period_id' => $period->id,
            'amount' => '1000.00',
        ]);

        SavingsLoan::factory()->create([
            'origin_period_id' => $origin->id,
            'due_period_id' => $due->id,
            'amount' => '150.00',
        ]);

        $summary = $this->app->make(FinanceService::class)->periodSummary($period);

        $this->assertSame('150.00', $summary['overdue_repayments']);
        $this->assertSame('150.00', $summary['loans_outstanding']);
        $this->assertSame('850.00', $summary['available_money']);
        $this->assertSame('150.00', $summary['committed_money']);
    }

    public function test_savings_balance_adds_deposits_and_repayments_and_subtracts_withdrawals(): void
    {
        $this->configureSettings('0.00', '300.00');

        $period = Period::factory()->create(['year' => 2031, 'month' => 5]);

        $this->createMovement($period, 'opening_balance', '1000.00');
        $this->createMovement($period, 'deposit', '200.00');
        $this->createMovement($period, 'withdrawal', '50.00');
        $this->createMovement($period, 'repayment', '30.00');

        $finance = $this->app->make(FinanceService::class);

        $this->assertSame('1180.00', $finance->savingsBalance());
        $this->assertSame('880.00', $finance->loanableAmount());

        $this->configureSettings('0.00', '2000.00');

        $this->assertSame('0.00', $this->app->make(FinanceService::class)->loanableAmount());
    }

    private function configureSettings(string $goal, string $minimum): void
    {
        $this->app->make(SettingsService::class)->updateSettings([
            'monthly_savings_goal' => $goal,
            'minimum_savings_balance' => $minimum,
        ]);
    }

    private function createMovement(Period $period, string $type, string $amount): SavingsMovement
    {
        return SavingsMovement::factory()->create([
            'period_id' => $period->id,
            'type' => $type,
            'amount' => $amount,
        ]);
    }
}
