<?php

namespace Tests\Feature;

use App\Enums\PeriodStatus;
use App\Models\Expense;
use App\Models\Income;
use App\Models\Period;
use App\Models\SavingsLoan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_application_page_renders_for_an_authenticated_user(): void
    {
        $user = User::factory()->create();

        $period = Period::factory()->create([
            'year' => 2034,
            'month' => 3,
            'variable_budget' => '250.00',
        ]);

        $duePeriod = Period::factory()->create([
            'year' => 2034,
            'month' => 4,
        ]);

        Income::factory()->received()->create([
            'period_id' => $period->id,
            'amount' => '900.00',
        ]);

        Expense::factory()->paid()->create([
            'period_id' => $period->id,
            'planned_amount' => '200.00',
        ]);

        Expense::factory()->variable()->paid()->create([
            'period_id' => $period->id,
            'planned_amount' => '50.00',
        ]);

        SavingsLoan::factory()->create([
            'origin_period_id' => $period->id,
            'due_period_id' => $duePeriod->id,
            'amount' => '30.00',
        ]);

        $this->actingAs($user);

        $urls = [
            route('dashboard', ['period' => $period->id]),
            route('budget.index', ['period' => $period->id]),
            route('incomes.index', ['period' => $period->id]),
            route('expenses.index', ['period' => $period->id]),
            route('savings.index', ['period' => $period->id]),
            route('loans.index', ['period' => $period->id]),
            route('periods.index'),
            route('periods.show', $period),
            route('settings.edit'),
        ];

        foreach ($urls as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_closed_period_page_shows_the_saved_summary(): void
    {
        $user = User::factory()->create();

        $period = Period::factory()->closed()->create([
            'year' => 2034,
            'month' => 5,
            'closing_summary' => [
                'income_received' => '1000.00',
                'expenses_paid' => '400.00',
                'savings_deposited' => '100.00',
                'savings_balance' => '100.00',
                'loans_originated' => '0.00',
                'repayments_made' => '0.00',
                'pending_debt' => '0.00',
                'variable_budget' => '200.00',
                'variable_spent' => '200.00',
                'available_money' => '500.00',
            ],
        ]);

        $this->actingAs($user)
            ->get(route('periods.show', $period))
            ->assertOk()
            ->assertSee('Resumen guardado al cierre');
    }

    public function test_dashboard_creates_the_current_period(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('dashboard'))
            ->assertOk();

        $this->assertDatabaseHas('periods', [
            'year' => now()->year,
            'month' => now()->month,
        ]);
    }

    public function test_closed_period_is_not_reopened_by_refreshing_its_status(): void
    {
        $period = Period::factory()->closed()->create([
            'year' => 2034,
            'month' => 6,
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('periods.show', $period))
            ->assertOk();

        $this->assertSame(PeriodStatus::Closed, $period->refresh()->status);
    }
}
