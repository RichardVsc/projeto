<?php

declare(strict_types=1);

namespace App\Domain\User;

use App\Domain\User\Exception\InvalidEmailException;

final class Email
{
    private string $value;

    private function __construct(string $email)
    {
        $trimmed = trim($email);

        if ($trimmed === '') {
            throw InvalidEmailException::empty();
        }

        if (! filter_var($trimmed, FILTER_VALIDATE_EMAIL)) {
            throw InvalidEmailException::invalidFormat($email);
        }

        $this->value = strtolower($trimmed);
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function equals(Email $other): bool
    {
        return $this->value === $other->value;
    }

    public function getValue(): string
    {
        return $this->value;
    }
}
