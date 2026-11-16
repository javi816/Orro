<?php

namespace App\Support;

use App\Models\Period;
use Illuminate\Support\Collection;

/**
 * Convierte los períodos en opciones para <native:select>.
 *
 * El elemento nativo trabaja con cadenas visibles, así que la etiqueta es
 * la clave de selección y aquí se resuelve de vuelta al id.
 */
final class PeriodOptions
{
    /**
     * @param  Collection<int, Period>  $periods
     * @return array<int, string>
     */
    public static function labels(Collection $periods): array
    {
        return $periods->map(fn (Period $period) => $period->label())->values()->all();
    }

    /**
     * @param  Collection<int, Period>  $periods
     */
    public static function idFromLabel(Collection $periods, string $label): ?int
    {
        $period = $periods->first(fn (Period $period) => $period->label() === $label);

        return $period?->id;
    }

    /**
     * @param  Collection<int, Period>  $periods
     */
    public static function labelFor(Collection $periods, ?int $id): string
    {
        $period = $periods->firstWhere('id', $id) ?? $periods->first();

        return $period?->label() ?? '';
    }
}
