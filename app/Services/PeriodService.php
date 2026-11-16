<?php

namespace App\Services;

use App\Enums\PeriodStatus;
use App\Models\Period;
use App\Repositories\PeriodRepository;
use App\Support\Money;
use DomainException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PeriodService
{
    public function __construct(
        private readonly PeriodRepository $periods,
        private readonly IncomeService $incomes,
        private readonly ExpenseService $expenses,
        private readonly FinanceService $finance,
    ) {}

    /**
     * @return Collection<int, Period>
     */
    public function periods(): Collection
    {
        return $this->periods->all();
    }

    public function current(): Period
    {
        $existing = $this->periods->findByYearMonth(now()->year, now()->month);

        if ($existing !== null) {
            return $existing->isClosed()
                ? $this->ensure(now()->year, now()->month)
                : $existing->refresh();
        }

        // No crear automáticamente un nuevo período por cambio de mes:
        // se reutiliza el último período abierto hasta que el usuario lo cierre.
        $latest = $this->periods->all()->first();

        if ($latest !== null && ! $latest->isClosed()) {
            return $latest->refresh();
        }

        return $this->ensure(now()->year, now()->month);
    }

    public function resolve(?int $periodId): Period
    {
        return $periodId ? $this->periods->find($periodId) : $this->current();
    }

    /**
     * Garantiza que el período exista y que sus registros esperados estén
     * generados. La generación es idempotente.
     */
    public function ensure(int $year, int $month): Period
    {
        $period = $this->periods->firstOrCreate($year, $month);

        if (! $period->isClosed()) {
            $this->incomes->generateExpected($period);
            $this->expenses->generateFixed($period);
        }

        return $period->refresh();
    }

    public function updateVariableBudget(Period $period, string $budget): Period
    {
        if ($period->isClosed()) {
            throw new DomainException('El período está cerrado y no admite modificaciones.');
        }

        $budget = Money::normalize($budget);

        if (Money::compare($budget, '0.00') < 0) {
            throw new DomainException('El presupuesto variable no puede ser negativo.');
        }

        return $this->periods->update($period, ['variable_budget' => $budget]);
    }

    /**
     * Evalúa las condiciones de cierre del período.
     *
     * @return array{can_close: bool, checks: array<int, array{label: string, ok: bool, detail: string}>, warnings: array<int, string>, summary: array<string, mixed>}
     */
    public function readiness(Period $period): array
    {
        $summary = $this->finance->periodSummary($period);

        $checks = [
            [
                'label' => 'Gastos fijos pagados',
                'ok' => $summary['fixed_pending_count'] === 0,
                'detail' => $summary['fixed_pending_count'] === 0
                    ? 'No quedan gastos fijos pendientes.'
                    : $summary['fixed_pending_count'].' gasto(s) fijo(s) pendiente(s) por '.Money::format($summary['fixed_pending']).'.',
            ],
            [
                'label' => 'Gastos variables pagados',
                'ok' => $summary['variable_pending_count'] === 0,
                'detail' => $summary['variable_pending_count'] === 0
                    ? 'No quedan gastos variables pendientes.'
                    : $summary['variable_pending_count'].' gasto(s) variable(s) pendiente(s) por '.Money::format($summary['variable_pending']).'.',
            ],
            [
                'label' => 'Meta mensual de ahorro cumplida',
                'ok' => Money::compare($summary['savings_missing'], '0.00') === 0,
                'detail' => Money::compare($summary['savings_missing'], '0.00') === 0
                    ? 'Aportado '.Money::format($summary['savings_deposited']).' de '.Money::format($summary['savings_goal']).'.'
                    : 'Faltan '.Money::format($summary['savings_missing']).' para cumplir la meta.',
            ],
            [
                'label' => 'Sin reposiciones vencidas pendientes',
                'ok' => Money::compare($summary['overdue_repayments'], '0.00') === 0,
                'detail' => Money::compare($summary['overdue_repayments'], '0.00') === 0
                    ? 'No hay préstamos vencidos sin reponer.'
                    : 'Hay '.Money::format($summary['overdue_repayments']).' en reposiciones vencidas.',
            ],
        ];

        $warnings = [];

        if ($summary['income_pending_count'] > 0) {
            $warnings[] = 'Hay '.$summary['income_pending_count'].' ingreso(s) pendiente(s) por '
                .Money::format($summary['income_pending']).'. No impiden el cierre, pero conviene revisarlos.';
        }

        $canClose = ! $period->isClosed()
            && collect($checks)->every(fn (array $check) => $check['ok']);

        return [
            'can_close' => $canClose,
            'checks' => $checks,
            'warnings' => $warnings,
            'summary' => $summary,
        ];
    }

    public function refreshStatus(Period $period): Period
    {
        if ($period->isClosed()) {
            return $period;
        }

        $readiness = $this->readiness($period);
        $status = $readiness['can_close'] ? PeriodStatus::ReadyToClose : PeriodStatus::Open;

        if ($period->status !== $status) {
            return $this->periods->update($period, ['status' => $status]);
        }

        return $period;
    }

    public function close(Period $period): Period
    {
        return DB::transaction(function () use ($period) {
            $locked = Period::query()->whereKey($period->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->isClosed()) {
                throw new DomainException('El período ya está cerrado.');
            }

            $readiness = $this->readiness($locked);

            if (! $readiness['can_close']) {
                $pending = collect($readiness['checks'])
                    ->reject(fn (array $check) => $check['ok'])
                    ->pluck('label')
                    ->implode(', ');

                throw new DomainException('No se puede cerrar el período. Pendiente: '.$pending.'.');
            }

            $closed = $this->periods->update($locked, [
                'status' => PeriodStatus::Closed,
                'closed_at' => now(),
                'closing_summary' => $this->finance->closingSummary($locked),
            ]);

            // Crear el período siguiente solo al cerrar el actual.
            $date = Carbon::createFromDate($locked->year, $locked->month, 1)->addMonth();
            $this->periods->firstOrCreate($date->year, $date->month);

            return $closed;
        });
    }
}
