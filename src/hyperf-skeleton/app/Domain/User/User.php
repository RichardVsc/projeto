<?php

declare(strict_types=1);

namespace App\Domain\User;

use App\Domain\Money\Money;
use App\Domain\User\Exception\NegativeWalletBalanceException;
use App\Domain\User\Exception\UserCannotSendMoneyException;
use App\Domain\User\Exception\UserInsufficientFundsException;

final class User
{
    private UserId $id;
    private string $name;
    private Email $email;
    private UserType $type;
    private Wallet $wallet;
    private DocumentNumber $document;

    private function __construct(
        UserId $id,
        string $name,
        Email $email,
        UserType $type,
        Wallet $wallet,
        DocumentNumber $document
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->email = $email;
        $this->type = $type;
        $this->document = $document;
        $this->wallet = $wallet;
    }

    public static function create(
        UserId $id,
        string $name,
        Email $email,
        UserType $type,
        Wallet $wallet,
        DocumentNumber $document
    ): self {
        return new self($id, $name, $email, $type, $wallet, $document);
    }

    public function getId(): UserId
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEmail(): Email
    {
        return $this->email;
    }

    public function getType(): UserType
    {
        return $this->type;
    }

    public function getWallet(): Wallet
    {
        return $this->wallet;
    }

    public function getDocument(): DocumentNumber
    {
        return $this->document;
    }

    public function canSendMoney(): bool
    {
        return $this->type->canSend();
    }

    public function hasSufficientBalance(Money $amount): bool
    {
        return $this->wallet->hasBalance($amount);
    }

    public function creditWallet(Money $amount): void
    {
        if (!$this->canSendMoney()) {
            throw new UserCannotSendMoneyException();
        }

        $this->wallet = $this->wallet->withAddedBalance($amount);
    }

    public function debitWallet(Money $amount): void
    {
        if (!$this->canSendMoney()) {
            throw new UserCannotSendMoneyException();
        }

        if (!$this->hasSufficientBalance($amount)) {
            throw UserInsufficientFundsException::notEnoughBalance();
        }

        $this->wallet = $this->wallet->withDeductedBalance($amount);
    }
}
