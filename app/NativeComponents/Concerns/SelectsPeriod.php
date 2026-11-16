<?php

namespace App\NativeComponents\Concerns;

use App\Models\Period;
use App\Services\PeriodService;
use App\Support\PeriodOptions;
use Illuminate\Database\Eloquent\Collection;

/**
 * Estado compartido por las pantallas que permiten elegir período.
 *
 * El elemento nativo de selección trabaja con etiquetas visibles, así que
 * `periodLabel` es la clave y se resuelve de vuelta al id del período.
 */
trait SelectsPeriod
{
    public ?int $periodId = null;

    public string $periodLabel = '';

    protected function bootPeriod(): Period
    {
        $service = app(PeriodService::class);
        $periods = $service->periods();

        if ($periods->isEmpty()) {
            return $this->applyPeriod($service->current());
        }

        $period = $periods->firstWhere('id', $this->periodId) ?? $periods->first();

        return $this->applyPeriod($period);
    }

    protected function applyPeriod(Period $period): Period
    {
        $this->periodId = $period->id;
        $this->periodLabel = $period->label();

        return $period;
    }

    public function choosePeriod(string $label): void
    {
        $service = app(PeriodService::class);
        $periods = $service->periods();
        $periodId = PeriodOptions::idFromLabel($periods, $label);

        if ($periodId === null) {
            return;
        }

        $this->applyPeriod($periods->firstWhere('id', $periodId));
    }

    /**
     * @return Collection<int, Period>
     */
    protected function periodCollection(): Collection
    {
        return app(PeriodService::class)->periods();
    }

    /**
     * @return array<int, string>
     */
    protected function periodLabels(): array
    {
        return PeriodOptions::labels($this->periodCollection());
    }
}
