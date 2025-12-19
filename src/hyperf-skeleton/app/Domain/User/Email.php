<?php

declare(strict_types=1);

namespace App\Domain\User;

use App\Domain\User\Exception\InvalidEmailException;

final class Email
{
    private string $value;

    private function __construct(string $email)
    {
        $email = trim($email);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw InvalidEmailException::invalidFormat();
        }

        $this->value = $email;
    }

    public static function fromString(string $email): self
    {
        return new self($email);
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function equals(Email $other): bool
    {
        return $this->value === (string) $other;
    }

    public function getValue(): string
    {
        return $this->value;
    }
}
