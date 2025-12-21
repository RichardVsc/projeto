<?php

declare(strict_types=1);

namespace App\Domain\User;

use App\Domain\User\Exception\InvalidWalletIdException;
use Symfony\Component\Uid\Uuid;

final class WalletId
{
    private string $value;

    private function __construct(string $value)
    {
        $trimmed = trim($value);

        if ($trimmed === '') {
            throw InvalidWalletIdException::empty();
        }

        if (! Uuid::isValid($trimmed)) {
            throw InvalidWalletIdException::invalidFormat($trimmed);
        }

        $this->value = $trimmed;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public static function generate(): self
    {
        return new self(Uuid::v4()->toRfc4122());
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function equals(WalletId $other): bool
    {
        return $this->value === $other->value;
    }
}
