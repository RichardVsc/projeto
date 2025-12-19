<?php

declare(strict_types=1);

namespace App\Domain\Wallet;

use App\Domain\Money\Money;
use App\Domain\Wallet\Exception\NegativeWalletBalanceException;
use App\Domain\Wallet\Exception\InsufficientBalanceException;
use App\Domain\Wallet\Exception\InvalidCreditAmountException;
use App\Domain\Wallet\Exception\InvalidDebitAmountException;
use App\Domain\Wallet\Exception\WalletCannotSendException;

final class Wallet
{
    private WalletId $id;
    private Money $balance;
    private WalletType $type;

    public function __construct(
        WalletId $id,
        Money $balance,
        WalletType $type
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

    public function canSendMoney(): bool
    {
        return $this->type->canSend();
    }

    public function credit(Money $amount): void
    {
        if (! $amount->isPositive()) {
            throw InvalidCreditAmountException::mustBePositive();
        }

        $this->balance = $this->balance->add($amount);
    }

    public function debit(Money $amount): void
    {
        if (! $amount->isPositive()) {
            throw InvalidDebitAmountException::amountMustBePositive();
        }

        if ($this->type->canSend() === false) {
            throw WalletCannotSendException::typeDoesNotAllowSending();
        }

        if ($this->balance->isLessThan($amount)) {
            throw InsufficientBalanceException::notEnoughFunds();
        }

        $this->balance = $this->balance->subtract($amount);
    }
}
