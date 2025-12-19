<?php

declare(strict_types=1);

namespace HyperfTest\Domain\Money;

use App\Domain\Money\Money;
use PHPUnit\Framework\TestCase;

final class MoneyTest extends TestCase
{
    public function test_it_can_be_created_from_int(): void
    {
        $positive = Money::fromCents(100);
        $zero     = Money::fromCents(0);
        $negative = Money::fromCents(-50);

        $this->assertSame(100, $positive->toInt(), 'Positive value failed');
        $this->assertSame(0, $zero->toInt(), 'Zero value failed');
        $this->assertSame(-50, $negative->toInt(), 'Negative value failed');
    }

    public function test_it_is_immutable(): void
    {
        $original   = Money::fromCents(100);
        $addResult  = $original->add(Money::fromCents(50));
        $subResult  = $original->subtract(Money::fromCents(30));

        $this->assertSame(100, $original->toInt());
        $this->assertSame(150, $addResult->toInt());
        $this->assertSame(70, $subResult->toInt());

        $this->assertNotSame($original, $addResult);
        $this->assertNotSame($original, $subResult);
        $this->assertNotSame($addResult, $subResult);
    }

    public function test_it_can_be_positive_zero_or_negative(): void
    {
        $cases = [
            ['money' => Money::fromCents(10), 'state' => 'positive'],
            ['money' => Money::fromCents(0),  'state' => 'zero'],
            ['money' => Money::fromCents(-10), 'state' => 'negative'],
        ];

        foreach ($cases as $case) {
            $money = $case['money'];
            $state = $case['state'];

            $this->assertSame($state === 'positive', $money->isPositive());
            $this->assertSame($state === 'negative', $money->isNegative());
            $this->assertSame($state === 'zero', $money->isZero());
        }
    }

    public function test_it_adds_money_correctly(): void
    {
        $cases = [
            ['a' => Money::fromCents(100), 'b' => Money::fromCents(50),  'expected' => 150],
            ['a' => Money::fromCents(0),   'b' => Money::fromCents(50),  'expected' => 50],
            ['a' => Money::fromCents(-20), 'b' => Money::fromCents(50),  'expected' => 30],
            ['a' => Money::fromCents(100), 'b' => Money::fromCents(-30), 'expected' => 70],
            ['a' => Money::fromCents(0),   'b' => Money::fromCents(0),   'expected' => 0],
        ];

        foreach ($cases as $case) {
            $result = $case['a']->add($case['b']);
            $this->assertSame($case['expected'], $result->toInt());
        }
    }

    public function test_it_subtracts_money_correctly(): void
    {
        $cases = [
            ['a' => Money::fromCents(100), 'b' => Money::fromCents(50),  'expected' => 50],
            ['a' => Money::fromCents(0),   'b' => Money::fromCents(50),  'expected' => -50],
            ['a' => Money::fromCents(-20), 'b' => Money::fromCents(50),  'expected' => -70],
            ['a' => Money::fromCents(100), 'b' => Money::fromCents(-30), 'expected' => 130],
            ['a' => Money::fromCents(0),   'b' => Money::fromCents(0),   'expected' => 0],
        ];

        foreach ($cases as $case) {
            $result = $case['a']->subtract($case['b']);
            $this->assertSame($case['expected'], $result->toInt());
        }
    }

    public function test_it_handles_large_values(): void
    {
        $large = Money::fromCents(PHP_INT_MAX - 1);
        $small = Money::fromCents(1);
        $result = $large->add($small);
        $this->assertSame(PHP_INT_MAX, $result->toInt());

        $largeNegative = Money::fromCents(PHP_INT_MIN + 1);
        $smallNegative = Money::fromCents(1);
        $resultNegative = $largeNegative->subtract($smallNegative);
        $this->assertSame(PHP_INT_MIN, $resultNegative->toInt());
    }

    public function test_comparisons(): void
    {
        $cases = [
            ['a' => Money::fromCents(50),  'b' => Money::fromCents(100), 'less' => true,  'greater' => false, 'equals' => false],
            ['a' => Money::fromCents(100), 'b' => Money::fromCents(50),  'less' => false, 'greater' => true,  'equals' => false],
            ['a' => Money::fromCents(100), 'b' => Money::fromCents(100), 'less' => false, 'greater' => false, 'equals' => true],
            ['a' => Money::fromCents(-10), 'b' => Money::fromCents(0),   'less' => true,  'greater' => false, 'equals' => false],
            ['a' => Money::fromCents(0),   'b' => Money::fromCents(-10), 'less' => false, 'greater' => true,  'equals' => false],
            ['a' => Money::fromCents(0),   'b' => Money::fromCents(0),   'less' => false, 'greater' => false, 'equals' => true],
        ];

        foreach ($cases as $case) {
            $a = $case['a'];
            $b = $case['b'];

            $this->assertSame($case['less'],    $a->isLessThan($b));
            $this->assertSame($case['greater'], $a->isGreaterThan($b));
            $this->assertSame($case['equals'],  $a->equals($b));
        }
    }
}
