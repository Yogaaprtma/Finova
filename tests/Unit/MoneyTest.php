<?php

namespace Tests\Unit;

use App\ValueObjects\Money;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function test_can_instantiate_money_with_integer_amount(): void
    {
        $money = Money::fromInteger(100000);

        $this->assertSame(100000, $money->getAmount());
        $this->assertSame('IDR', $money->getCurrency());
    }

    public function test_can_instantiate_money_with_custom_currency(): void
    {
        $money = Money::fromInteger(50, 'USD');

        $this->assertSame(50, $money->getAmount());
        $this->assertSame('USD', $money->getCurrency());
    }

    public function test_can_add_money_with_same_currency(): void
    {
        $m1 = Money::fromInteger(50000);
        $m2 = Money::fromInteger(25000);

        $result = $m1->add($m2);

        $this->assertSame(75000, $result->getAmount());
        $this->assertSame('IDR', $result->getCurrency());
    }

    public function test_can_subtract_money_with_same_currency(): void
    {
        $m1 = Money::fromInteger(50000);
        $m2 = Money::fromInteger(20000);

        $result = $m1->subtract($m2);

        $this->assertSame(30000, $result->getAmount());
        $this->assertSame('IDR', $result->getCurrency());
    }

    public function test_throws_exception_when_adding_different_currencies(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Currency mismatch: IDR vs USD');

        $m1 = Money::fromInteger(50000, 'IDR');
        $m2 = Money::fromInteger(10, 'USD');

        $m1->add($m2);
    }

    public function test_throws_exception_when_subtracting_different_currencies(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Currency mismatch: IDR vs EUR');

        $m1 = Money::fromInteger(50000, 'IDR');
        $m2 = Money::fromInteger(10, 'EUR');

        $m1->subtract($m2);
    }

    public function test_boolean_predicates(): void
    {
        $positive = Money::fromInteger(100);
        $negative = Money::fromInteger(-50);
        $zero = Money::fromInteger(0);

        $this->assertTrue($positive->isPositive());
        $this->assertFalse($positive->isNegative());
        $this->assertFalse($positive->isZero());

        $this->assertTrue($negative->isNegative());
        $this->assertFalse($negative->isPositive());
        $this->assertFalse($negative->isZero());

        $this->assertTrue($zero->isZero());
        $this->assertFalse($zero->isPositive());
        $this->assertFalse($zero->isNegative());
    }

    public function test_format_rupiah(): void
    {
        $money = Money::fromInteger(1500000);
        $this->assertSame('Rp 1.500.000', $money->format());
    }
}
