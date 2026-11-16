<?php

namespace App\NativeComponents;

use App\Models\SavingsGoal;
use App\NativeComponents\Concerns\SelectsPeriod;
use App\Services\SavingsService;
use Illuminate\View\View;
use Native\Mobile\Edge\NativeComponent;

class SavingsScreen extends NativeComponent
{
    use SelectsPeriod;

    public function openGoal(?int $goalId): void
    {
        $this->navigate('/ahorros/'.($goalId ?? 'general'));
    }

    public function render(): View
    {
        $goals = SavingsGoal::query()->where('active', true)->orderBy('name')->get();
        $savings = app(SavingsService::class);

        $goalBalances = [];
        foreach ($goals as $goal) {
            $goalBalances[$goal->id] = $savings->balanceForGoal($goal->id);
        }

        return view('native.savings', [
            'goals' => $goals,
            'generalBalance' => $savings->balanceForGoal(null),
            'goalBalances' => $goalBalances,
        ]);
    }
}
