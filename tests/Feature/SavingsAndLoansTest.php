<?php

namespace Tests\Feature;

use App\Enums\SavingsMovementType;
use App\Models\Period;
use App\Services\LoanService;
use App\Services\SavingsService;
use App\Services\SettingsService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SavingsAndLoansTest extends TestCase
{
    use RefreshDatabase;

    public function test_loan_withdraws_from_savings_and_respects_the_protected_minimum(): void
    {
        $this->configureSettings('0.00', '100.00');

        $period = Period::factory()->create(['year' => 2032, 'month' => 3]);

        $savings = $this->app->make(SavingsService::class);
        $savings->register($period, ['type' => 'opening_balance', 'amount' => '500.00']);

        $loans = $this->app->make(LoanService::class);

        $this->assertSame('400.00', $loans->loanableAmount());

        $loan = $loans->create($period, [
            'amount' => '300.00',
            'description' => 'Arreglo del vehículo',
        ]);

        $this->assertSame('2032', (string) $loan->duePeriod->year);
        $this->assertSame('4', (string) $loan->duePeriod->month);
        $this->assertSame('200.00', $savings->balance());
        $this->assertSame('300.00', $loans->outstanding($loan));

        $withdrawal = $loan->movements()->where('type', SavingsMovementType::Withdrawal)->firstOrFail();
        $this->assertSame('300.00', $withdrawal->amount);
        $this->assertSame($period->id, $withdrawal->period_id);
    }

    public function test_partial_repayments_reduce_the_outstanding_balance(): void
    {
        $this->configureSettings('0.00', '0.00');

        $period = Period::factory()->create(['year' => 2032, 'month' => 5]);

        $savings = $this->app->make(SavingsService::class);
        $savings->register($period, ['type' => 'opening_balance', 'amount' => '500.00']);

        $loans = $this->app->make(LoanService::class);
        $loan = $loans->create($period, ['amount' => '300.00', 'description' => 'Préstamo']);

        $duePeriod = $loan->duePeriod;

        $loans->repay($loan, $duePeriod, ['amount' => '100.00']);
        $this->assertSame('200.00', $loans->outstanding($loan));

        $loans->repay($loan, $duePeriod, ['amount' => '200.00']);
        $this->assertSame('0.00', $loans->outstanding($loan));

        $this->assertSame('500.00', $savings->balance());
        $this->assertSame(0, $loans->pendingLoans()->count());
    }

    public function test_loan_cannot_exceed_the_loanable_amount(): void
    {
        $this->configureSettings('0.00', '100.00');

        $period = Period::factory()->create(['year' => 2032, 'month' => 7]);

        $this->app->make(SavingsService::class)
            ->register($period, ['type' => 'opening_balance', 'amount' => '500.00']);

        $this->expectException(DomainException::class);

        $this->app->make(LoanService::class)->create($period, [
            'amount' => '400.01',
            'description' => 'Demasiado grande',
        ]);
    }

    public function test_repayment_cannot_exceed_the_outstanding_balance(): void
    {
        $this->configureSettings('0.00', '0.00');

        $period = Period::factory()->create(['year' => 2032, 'month' => 9]);

        $this->app->make(SavingsService::class)
            ->register($period, ['type' => 'opening_balance', 'amount' => '500.00']);

        $loan = $this->app->make(LoanService::class)
            ->create($period, ['amount' => '300.00', 'description' => 'Préstamo']);

        $this->expectException(DomainException::class);

        $this->app->make(LoanService::class)
            ->repay($loan, $loan->duePeriod, ['amount' => '300.01']);
    }

    public function test_withdrawals_cannot_exceed_the_savings_balance(): void
    {
        $period = Period::factory()->create(['year' => 2032, 'month' => 11]);

        $savings = $this->app->make(SavingsService::class);
        $savings->register($period, ['type' => 'opening_balance', 'amount' => '100.00']);

        $this->expectException(DomainException::class);

        $savings->register($period, ['type' => 'withdrawal', 'amount' => '100.01']);
    }

    public function test_opening_balance_can_only_be_registered_once(): void
    {
        $period = Period::factory()->create(['year' => 2033, 'month' => 1]);

        $savings = $this->app->make(SavingsService::class);

        $this->assertFalse($savings->hasOpeningBalance());

        $savings->register($period, ['type' => 'opening_balance', 'amount' => '100.00']);

        $this->assertTrue($savings->hasOpeningBalance());

        $this->expectException(DomainException::class);

        $savings->register($period, ['type' => 'opening_balance', 'amount' => '50.00']);
    }

    public function test_repayments_are_created_only_through_the_loan_flow(): void
    {
        $period = Period::factory()->create(['year' => 2033, 'month' => 3]);

        $savings = $this->app->make(SavingsService::class);
        $savings->register($period, ['type' => 'opening_balance', 'amount' => '100.00']);

        $this->expectException(DomainException::class);

        $savings->register($period, ['type' => 'repayment', 'amount' => '10.00']);
    }

    public function test_loans_share_the_same_savings_pool(): void
    {
        $this->configureSettings('0.00', '0.00');

        $period = Period::factory()->create(['year' => 2033, 'month' => 5]);

        $this->app->make(SavingsService::class)
            ->register($period, ['type' => 'opening_balance', 'amount' => '500.00']);

        $loans = $this->app->make(LoanService::class);
        $loans->create($period, ['amount' => '300.00', 'description' => 'Primero']);

        $this->assertSame('200.00', $loans->loanableAmount());

        $this->expectException(DomainException::class);

        $loans->create($period, ['amount' => '200.01', 'description' => 'Segundo']);
    }

    private function configureSettings(string $goal, string $minimum): void
    {
        $this->app->make(SettingsService::class)->updateSettings([
            'monthly_savings_goal' => $goal,
            'minimum_savings_balance' => $minimum,
        ]);
    }
}
