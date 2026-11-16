<?php

namespace Tests\Unit;

use App\Support\Money;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function test_normalize_converts_comma_decimals_and_rounds_to_two_places(): void
    {
        $this->assertSame('1234.50', Money::normalize('1234,5'));
        $this->assertSame('0.00', Money::normalize('0'));
        $this->assertSame('-10.25', Money::normalize('-10.25'));
    }

    public function test_normalize_rejects_invalid_formats(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Money::normalize('10.999');
    }

    public function test_add_and_subtract_avoid_floating_point_errors(): void
    {
        $this->assertSame('0.30', Money::add('0.10', '0.20'));
        $this->assertSame('0.99', Money::sub('1.00', '0.01'));
        $this->assertSame('3.00', Money::add('0.10', '0.20', '2.70'));
    }

    public function test_comparisons_and_helpers(): void
    {
        $this->assertSame(1, Money::compare('1.00', '0.99'));
        $this->assertSame(0, Money::compare('1.00', '1'));
        $this->assertSame(-1, Money::compare('0.99', '1.00'));

        $this->assertTrue(Money::isPositive('0.01'));
        $this->assertFalse(Money::isPositive('0.00'));

        $this->assertSame('5.00', Money::max('2.50', '5.00'));
        $this->assertSame('5.00', Money::max('5.00', '2.50'));
    }

    public function test_total_sums_iterables_as_decimal_strings(): void
    {
        $this->assertSame('600.00', Money::total(['100.00', '200.00', '300.00']));
        $this->assertSame('0.00', Money::total([]));
    }

    public function test_format_uses_colombian_style_separators(): void
    {
        $this->assertSame('$ 1.234.567,89', Money::format('1234567.89'));
        $this->assertSame('$ 0,00', Money::format(null));
        $this->assertSame('-$ 50,00', Money::format('-50'));
    }
}
