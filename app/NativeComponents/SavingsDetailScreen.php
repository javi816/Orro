<?php

namespace App\NativeComponents;

use App\Models\SavingsGoal;
use App\NativeComponents\Concerns\SelectsPeriod;
use App\Services\PeriodService;
use App\Services\SavingsService;
use Illuminate\View\View;
use Native\Mobile\Edge\NativeComponent;

class SavingsDetailScreen extends NativeComponent
{
    use SelectsPeriod;

    public string $goal = 'general';

    public ?int $goalId = null;

    public string $goalName = 'Ahorro general';

    public string $monthlyGoal = '0.00';

    public string $movementType = 'deposit';

    public string $typeLabel = 'Aportación';

    public string $amount = '';

    public string $description = '';

    public string $movementDate = '';

    public string $error = '';

    public string $success = '';

    public function mount(): void
    {
        $this->bootPeriod();
        $this->movementDate = now()->toDateString();

        if ($this->goal !== 'general' && is_numeric($this->goal)) {
            $this->goalId = (int) $this->goal;
            $goal = SavingsGoal::query()->find($this->goalId);
            if ($goal) {
                $this->goalName = $goal->name;
                $this->monthlyGoal = (string) $goal->monthly_goal;
            }
        }

        $this->amount = $this->monthlyGoal !== '0.00' ? $this->monthlyGoal : '';
    }

    public function chooseType(string $label): void
    {
        $this->typeLabel = $label;
        $this->movementType = match ($label) {
            'Saldo inicial' => 'opening_balance',
            'Aportación' => 'deposit',
            'Retiro' => 'withdrawal',
            default => 'deposit',
        };
    }

    public function register(?string $submitted = null): void
    {
        $this->error = '';
        $this->success = '';

        try {
            $period = app(PeriodService::class)->refreshStatus($this->bootPeriod());
            app(SavingsService::class)->register($period, [
                'type' => $this->movementType,
                'amount' => trim($submitted ?? $this->amount),
                'description' => trim($this->description) !== '' ? trim($this->description) : null,
                'movement_date' => $this->movementDate !== '' ? $this->movementDate : now()->toDateString(),
                'savings_goal_id' => $this->goalId,
            ]);
        } catch (\DomainException|\InvalidArgumentException $exception) {
            $this->error = $exception->getMessage();

            return;
        }

        $this->amount = $this->monthlyGoal;
        $this->description = '';
        $this->success = 'Movimiento registrado.';
    }

    public function onBackPressed(): void
    {
        $this->replace('/ahorros');
    }

    public function render(): View
    {
        $goalId = $this->goalId;
        $savings = app(SavingsService::class);
        $balance = $savings->balanceForGoal($goalId);
        $progress = 0.0;
        if ((float) $this->monthlyGoal > 0) {
            // use final_goal if set
        }
        $goal = $goalId ? SavingsGoal::query()->find($goalId) : null;
        $finalGoal = $goal ? (float) $goal->final_goal : 0.0;
        if ($finalGoal > 0) {
            $progress = min(1.0, max(0.0, (float) $balance / $finalGoal));
        }

        return view('native.savings-detail', [
            'goalId' => $goalId,
            'goalName' => $this->goalName,
            'monthlyGoal' => $this->monthlyGoal,
            'finalGoal' => $finalGoal > 0 ? number_format($finalGoal, 2, '.', '') : '0.00',
            'balance' => $balance,
            'progress' => $progress,
            'movements' => $savings->movementsForGoal($goalId),
            'hasOpeningBalance' => $savings->hasOpeningBalance(),
        ]);
    }
}
