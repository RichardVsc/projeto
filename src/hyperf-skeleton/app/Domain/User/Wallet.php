<?php

declare(strict_types=1);

namespace App\Domain\User;

use App\Domain\Money\Money;
use App\Domain\User\Exception\NegativeWalletBalanceException;

final class Wallet
{
    private WalletId $id;
    private Money $balance;
    private UserType $type;

    public function __construct(
        WalletId $id,
        Money $balance,
        UserType $type
    ) {
        if ($balance->isNegative()) {
            throw NegativeWalletBalanceException::negativeBalance();
        }

        $this->id = $id;
        $this->balance = $balance;
        $this->type = $type;
    }

    public function getId(): WalletId
    {
        return $this->id;
    }

    public function getBalance(): Money
    {
        return $this->balance;
    }

    public function getType(): UserType
    {
        return $this->type;
    }

    public function canSendMoney(): bool
    {
        return $this->type->canSend();
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
            $newBalance,
            $this->type
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
            $newBalance,
            $this->type
        );
    }
}