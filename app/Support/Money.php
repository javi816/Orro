<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Utilidades para operar importes monetarios con BCMath.
 *
 * Los importes se manejan siempre como cadenas decimales de dos posiciones
 * para evitar errores de precisión de los números flotantes.
 */
final class Money
{
    public static function normalize(string|int|float|null $value): string
    {
        $value = str_replace(',', '.', trim((string) ($value ?? '0')));

        if ($value === '' || $value === '.') {
            $value = '0';
        }

        if (! preg_match('/^-?\d{1,12}(\.\d{1,2})?$/', $value)) {
            throw new InvalidArgumentException('El importe no tiene un formato válido.');
        }

        return bcadd($value, '0', 2);
    }

    public static function add(string ...$values): string
    {
        $total = '0.00';

        foreach ($values as $value) {
            $total = bcadd($total, self::normalize($value), 2);
        }

        return $total;
    }

    public static function sub(string $minuend, string $subtrahend): string
    {
        return bcsub(self::normalize($minuend), self::normalize($subtrahend), 2);
    }

    public static function mul(string $value, string $factor): string
    {
        return bcmul(self::normalize($value), self::normalize($factor), 2);
    }

    public static function compare(string $left, string $right): int
    {
        return bccomp(self::normalize($left), self::normalize($right), 2);
    }

    public static function isPositive(string $value): bool
    {
        return self::compare($value, '0.00') > 0;
    }

    public static function max(string $left, string $right): string
    {
        return self::compare($left, $right) >= 0 ? self::normalize($left) : self::normalize($right);
    }

    public static function total(iterable $values): string
    {
        $total = '0.00';

        foreach ($values as $value) {
            $total = bcadd($total, self::normalize((string) $value), 2);
        }

        return $total;
    }

    public static function format(string|int|float|null $value): string
    {
        $normalized = self::normalize($value ?? '0');
        $negative = str_starts_with($normalized, '-');
        [$integer, $decimals] = explode('.', ltrim($normalized, '-'));
        $integer = preg_replace('/\B(?=(\d{3})+(?!\d))/', '.', $integer);

        return ($negative ? '-' : '').'$ '.$integer.','.$decimals;
    }
}
