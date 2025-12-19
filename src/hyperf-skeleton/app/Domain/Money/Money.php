<?php

declare(strict_types=1);

namespace App\Domain\Money;

final class Money
{
    private int $amount;

    private function __construct(int $amount)
    {
        $this->amount = $amount;
    }

    public static function fromCents(int $cents): self
    {
        return new self($cents);
    }

    public function toInt(): int
    {
        return $this->amount;
    }

    public function add(Money $other): self
    {
        return new self($this->amount + $other->amount);
    }

    public function subtract(Money $other): self
    {
        return new self($this->amount - $other->amount);
    }

    public function isZero(): bool
    {
        return $this->amount === 0;
    }

    public function isNegative(): bool
    {
        return $this->amount < 0;
    }

    public function isPositive(): bool
    {
        return $this->amount > 0;
    }

    public function isLessThan(Money $other): bool
    {
        return $this->amount < $other->amount;
    }

    public function isLessThanOrEqual(Money $other): bool
    {
        return $this->amount <= $other->amount;
    }

    public function isGreaterThan(Money $other): bool
    {
        return $this->amount > $other->amount;
    }

    public function isGreaterThanOrEqual(Money $other): bool
    {
        return $this->amount >= $other->amount;
    }

    public function equals(Money $other): bool
    {
        return $this->amount === $other->amount;
    }
}
