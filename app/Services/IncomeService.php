<?php

namespace App\Services;

use App\Enums\IncomeStatus;
use App\Models\Income;
use App\Models\Period;
use App\Repositories\IncomeRepository;
use App\Repositories\IncomeSourceRepository;
use DomainException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class IncomeService
{
    public function __construct(
        private readonly IncomeRepository $incomes,
        private readonly IncomeSourceRepository $sources,
    ) {}

    /**
     * @return Collection<int, Income>
     */
    public function forPeriod(Period $period): Collection
    {
        return $this->incomes->forPeriod($period);
    }

    public function markReceived(Income $income): Income
    {
        $period = $income->period;

        if ($period->isClosed()) {
            throw new DomainException('El período está cerrado y no admite modificaciones.');
        }

        if ($income->status === IncomeStatus::Received) {
            throw new DomainException('El ingreso ya fue marcado como recibido.');
        }

        return $this->incomes->update($income, [
            'status' => IncomeStatus::Received,
            'received_at' => now()->toDateString(),
        ]);
    }

    /**
     * Genera los ingresos esperados desde las fuentes activas. Es idempotente.
     */
    public function generateExpected(Period $period): int
    {
        if ($period->isClosed()) {
            return 0;
        }

        return DB::transaction(function () use ($period) {
            $created = 0;

            foreach ($this->sources->active() as $source) {
                $payments = $source->frequency === 'biweekly' ? [1, 2] : [$source->payment_number];

                foreach ($payments as $paymentNumber) {
                    if ($this->incomes->existsForSource($period, $source, $paymentNumber)) {
                        continue;
                    }

                    $this->incomes->create([
                        'period_id' => $period->id,
                        'income_source_id' => $source->id,
                        'description' => $source->description,
                        'amount' => $source->amount,
                        'payment_number' => $paymentNumber,
                        'status' => IncomeStatus::Pending,
                    ]);

                    $created++;
                }
            }

            return $created;
        });
    }
}
