<?php

declare(strict_types=1);

namespace App\Domain\Transfer;

use App\Domain\Transfer\Exception\InvalidTransferIdException;
use Symfony\Component\Uid\Uuid;

final class TransferId
{
    private string $value;

    private function __construct(string $value)
    {
        $trimmed = trim($value);

        if ($trimmed === '') {
            throw InvalidTransferIdException::empty();
        }

        if (!Uuid::isValid($trimmed)) {
            throw InvalidTransferIdException::invalidFormat($trimmed);
        }

        $this->value = $trimmed;
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

    public function equals(TransferId $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
