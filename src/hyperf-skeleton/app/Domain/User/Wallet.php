<?php

declare(strict_types=1);

namespace App\Domain\User;

use App\Domain\Money\Money;
use App\Domain\User\Exception\NegativeWalletBalanceException;

final class Wallet
{
    private WalletId $id;
    private Money $balance;

    public function __construct(
        WalletId $id,
        Money $balance
    ) {
        if ($balance->isNegative()) {
            throw NegativeWalletBalanceException::negativeBalance();
        }

        $this->id = $id;
        $this->balance = $balance;
    }

    public function getId(): WalletId
    {
        return $this->id;
    }

    public function getBalance(): Money
    {
        return $this->balance;
    }

    public function hasBalance(Money $amount): bool
    {
        return $this->balance->isGreaterThanOrEqual($amount);
    }

    public function withAddedBalance(Money $amount): self
    {
        $newBalance = $this->balance->add($amount);
        
        return new self(
            $this->id,
            $newBalance
        );
    }

    public function withDeductedBalance(Money $amount): self
    {
        $newBalance = $this->balance->subtract($amount);
        
        if ($newBalance->isNegative()) {
            throw NegativeWalletBalanceException::negativeBalance();
        }
        
        return new self(
            $this->id,
            $newBalance
        );
    }
}