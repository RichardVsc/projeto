<?php

declare(strict_types=1);

namespace App\Domain\User;

use App\Domain\Money\Money;
use App\Domain\User\Exception\InvalidUserException;
use App\Domain\User\Exception\UserCannotSendMoneyException;
use App\Domain\User\Exception\UserInsufficientFundsException;

final class User
{
    private UserId $id;
    private UserType $type;
    private string $name;
    private DocumentNumber $document;
    private Email $email;
    private HashedPassword $password;
    private Wallet $wallet;

    public function __construct(
        UserId $id,
        UserType $type,
        string $name,
        DocumentNumber $document,
        Email $email,
        HashedPassword $password,
        Wallet $wallet
    ) {
        $this->id = $id;
        $this->type = $type;
        $this->name = $this->assertValidName($name);;
        $this->document = $document;
        $this->email = $email;
        $this->password = $password;
        $this->wallet = $wallet;
    }

    public function getId(): UserId
    {
        return $this->id;
    }

    public function getType(): UserType
    {
        return $this->type;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDocument(): DocumentNumber
    {
        return $this->document;
    }

    public function getEmail(): Email
    {
        return $this->email;
    }

    public function getWallet(): Wallet
    {
        return $this->wallet;
    }

    public function verifyPassword(string $plainText): bool
    {
        return $this->password->verify($plainText);
    }

    public function canSendMoney(): bool
    {
        return $this->type->canSend();
    }

    public function hasSufficientBalance(Money $amount): bool
    {
        return $this->wallet->hasBalance($amount);
    }

    public function creditWallet(Money $amount): self
    {
        $newWallet = $this->wallet->withAddedBalance($amount);

        return new self(
            $this->id,
            $this->type,
            $this->name,
            $this->document,
            $this->email,
            $this->password,
            $newWallet
        );
    }

    public function debitWallet(Money $amount): self
    {
        if (!$this->canSendMoney()) {
            throw UserCannotSendMoneyException::cannotSendMoney();
        }

        if (!$this->hasSufficientBalance($amount)) {
            throw UserInsufficientFundsException::notEnoughBalance();
        }

        $newWallet = $this->wallet->withDeductedBalance($amount);

        return new self(
            $this->id,
            $this->type,
            $this->name,
            $this->document,
            $this->email,
            $this->password,
            $newWallet
        );
    }

    private function assertValidName(string $name): string
    {
        $trimmed = trim($name);

        if ($trimmed === '') {
            throw InvalidUserException::emptyName();
        }

        if (mb_strlen($trimmed) < 3) {
            throw InvalidUserException::nameTooShort();
        }

        if (mb_strlen($trimmed) > 255) {
            throw InvalidUserException::nameTooLong();
        }

        return $trimmed;
    }
}
