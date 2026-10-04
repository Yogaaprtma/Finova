<?php

namespace App\ValueObjects;

use InvalidArgumentException;

class Money
{
    private int $amount;
    private string $currency;

    public function __construct(int $amount, string $currency = 'IDR')
    {
        $this->amount = $amount;
        $this->currency = strtoupper($currency);
    }

    public static function fromInteger(int $amount, string $currency = 'IDR'): self
    {
        return new self($amount, $currency);
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function add(Money $other): self
    {
        $this->assertSameCurrency($other);
        return new self($this->amount + $other->getAmount(), $this->currency);
    }

    public function subtract(Money $other): self
    {
        $this->assertSameCurrency($other);
        return new self($this->amount - $other->getAmount(), $this->currency);
    }

    public function isPositive(): bool
    {
        return $this->amount > 0;
    }

    public function isNegative(): bool
    {
        return $this->amount < 0;
    }

    public function isZero(): bool
    {
        return $this->amount === 0;
    }

    public function format(): string
    {
        return 'Rp ' . number_format($this->amount, 0, ',', '.');
    }

    private function assertSameCurrency(Money $other): void
    {
        if ($this->currency !== $other->getCurrency()) {
            throw new InvalidArgumentException("Currency mismatch: {$this->currency} vs {$other->getCurrency()}");
        }
    }
}
