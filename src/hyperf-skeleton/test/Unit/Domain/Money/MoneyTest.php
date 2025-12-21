<?php

declare(strict_types=1);

namespace HyperfTest\Unit\Domain\Money;

use App\Domain\Money\Money;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class MoneyTest extends TestCase
{
    public function testCanBeCreatedFromCents(): void
    {
        $positive = Money::fromCents(100);
        $zero = Money::fromCents(0);
        $negative = Money::fromCents(-50);

        $this->assertSame(100, $positive->toInt());
        $this->assertSame(0, $zero->toInt());
        $this->assertSame(-50, $negative->toInt());
    }

    public function testIsImmutable(): void
    {
        $original = Money::fromCents(100);
        $added = $original->add(Money::fromCents(50));
        $subtracted = $original->subtract(Money::fromCents(30));

        $this->assertSame(100, $original->toInt());

        $this->assertSame(150, $added->toInt());
        $this->assertSame(70, $subtracted->toInt());

        $this->assertNotSame($original, $added);
        $this->assertNotSame($original, $subtracted);
        $this->assertNotSame($added, $subtracted);
    }

    public function testCanIdentifyPositiveZeroAndNegative(): void
    {
        $positive = Money::fromCents(10);
        $zero = Money::fromCents(0);
        $negative = Money::fromCents(-10);

        $this->assertTrue($positive->isPositive());
        $this->assertFalse($positive->isZero());
        $this->assertFalse($positive->isNegative());

        $this->assertFalse($zero->isPositive());
        $this->assertTrue($zero->isZero());
        $this->assertFalse($zero->isNegative());

        $this->assertFalse($negative->isPositive());
        $this->assertFalse($negative->isZero());
        $this->assertTrue($negative->isNegative());
    }

    public function testAddsMoneyCorrectly(): void
    {
        $this->assertSame(150, Money::fromCents(100)->add(Money::fromCents(50))->toInt());
        $this->assertSame(50, Money::fromCents(0)->add(Money::fromCents(50))->toInt());
        $this->assertSame(30, Money::fromCents(-20)->add(Money::fromCents(50))->toInt());
        $this->assertSame(70, Money::fromCents(100)->add(Money::fromCents(-30))->toInt());
        $this->assertSame(0, Money::fromCents(0)->add(Money::fromCents(0))->toInt());
    }

    public function testSubtractsMoneyCorrectly(): void
    {
        $this->assertSame(50, Money::fromCents(100)->subtract(Money::fromCents(50))->toInt());
        $this->assertSame(-50, Money::fromCents(0)->subtract(Money::fromCents(50))->toInt());
        $this->assertSame(-70, Money::fromCents(-20)->subtract(Money::fromCents(50))->toInt());
        $this->assertSame(130, Money::fromCents(100)->subtract(Money::fromCents(-30))->toInt());
        $this->assertSame(0, Money::fromCents(0)->subtract(Money::fromCents(0))->toInt());
    }

    public function testHandlesLargeValues(): void
    {
        $large = Money::fromCents(PHP_INT_MAX - 1);
        $result = $large->add(Money::fromCents(1));
        $this->assertSame(PHP_INT_MAX, $result->toInt());

        $largeNegative = Money::fromCents(PHP_INT_MIN + 1);
        $resultNegative = $largeNegative->subtract(Money::fromCents(1));
        $this->assertSame(PHP_INT_MIN, $resultNegative->toInt());
    }

    public function testEqualsComparison(): void
    {
        $money100a = Money::fromCents(100);
        $money100b = Money::fromCents(100);
        $money50 = Money::fromCents(50);

        $this->assertTrue($money100a->equals($money100b));
        $this->assertFalse($money100a->equals($money50));
        $this->assertTrue(Money::fromCents(0)->equals(Money::fromCents(0)));
        $this->assertTrue(Money::fromCents(-10)->equals(Money::fromCents(-10)));
    }

    public function testIsGreaterThanComparison(): void
    {
        $this->assertTrue(Money::fromCents(100)->isGreaterThan(Money::fromCents(50)));
        $this->assertFalse(Money::fromCents(50)->isGreaterThan(Money::fromCents(100)));
        $this->assertFalse(Money::fromCents(100)->isGreaterThan(Money::fromCents(100)));
        $this->assertTrue(Money::fromCents(0)->isGreaterThan(Money::fromCents(-10)));
        $this->assertFalse(Money::fromCents(-10)->isGreaterThan(Money::fromCents(0)));
    }

    public function testIsGreaterThanOrEqualComparison(): void
    {
        $this->assertTrue(Money::fromCents(100)->isGreaterThanOrEqual(Money::fromCents(50)));
        $this->assertTrue(Money::fromCents(100)->isGreaterThanOrEqual(Money::fromCents(100)));
        $this->assertFalse(Money::fromCents(50)->isGreaterThanOrEqual(Money::fromCents(100)));
        $this->assertTrue(Money::fromCents(0)->isGreaterThanOrEqual(Money::fromCents(-10)));
        $this->assertTrue(Money::fromCents(0)->isGreaterThanOrEqual(Money::fromCents(0)));
    }

    public function testIsLessThanComparison(): void
    {
        $this->assertTrue(Money::fromCents(50)->isLessThan(Money::fromCents(100)));
        $this->assertFalse(Money::fromCents(100)->isLessThan(Money::fromCents(50)));
        $this->assertFalse(Money::fromCents(100)->isLessThan(Money::fromCents(100)));
        $this->assertTrue(Money::fromCents(-10)->isLessThan(Money::fromCents(0)));
        $this->assertFalse(Money::fromCents(0)->isLessThan(Money::fromCents(-10)));
    }

    public function testIsLessThanOrEqualComparison(): void
    {
        $this->assertTrue(Money::fromCents(50)->isLessThanOrEqual(Money::fromCents(100)));
        $this->assertTrue(Money::fromCents(100)->isLessThanOrEqual(Money::fromCents(100)));
        $this->assertFalse(Money::fromCents(100)->isLessThanOrEqual(Money::fromCents(50)));
        $this->assertTrue(Money::fromCents(-10)->isLessThanOrEqual(Money::fromCents(0)));
        $this->assertTrue(Money::fromCents(0)->isLessThanOrEqual(Money::fromCents(0)));
    }

    public function testComparisonWithNegativeValues(): void
    {
        $this->assertTrue(Money::fromCents(-100)->isLessThan(Money::fromCents(-50)));
        $this->assertFalse(Money::fromCents(-50)->isLessThan(Money::fromCents(-100)));
        $this->assertTrue(Money::fromCents(-50)->isGreaterThan(Money::fromCents(-100)));
        $this->assertFalse(Money::fromCents(-100)->isGreaterThan(Money::fromCents(-50)));
    }
}
