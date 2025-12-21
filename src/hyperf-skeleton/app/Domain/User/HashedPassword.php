<?php

declare(strict_types=1);

namespace App\Domain\User;

use App\Domain\User\Exception\InvalidHashedPasswordException;

final class HashedPassword
{
    private const MIN_LENGTH = 8;

    private string $hash;

    private function __construct(string $hash)
    {
        $this->hash = $hash;
    }

    public static function fromPlainText(string $plainText): self
    {
        self::assertMinimumLength($plainText);

        return new self(self::hashPassword($plainText));
    }

    public static function fromHash(string $hash): self
    {
        if (! self::isValidHash($hash)) {
            throw InvalidHashedPasswordException::invalidFormat();
        }

        return new self($hash);
    }

    public function verify(string $plainText): bool
    {
        return password_verify($plainText, $this->hash);
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    private static function assertMinimumLength(string $plainText): void
    {
        if ($plainText === '') {
            throw InvalidHashedPasswordException::empty();
        }

        if (mb_strlen($plainText) < self::MIN_LENGTH) {
            throw InvalidHashedPasswordException::invalidLength(self::MIN_LENGTH);
        }
    }

    private static function hashPassword(string $plainText): string
    {
        return password_hash($plainText, PASSWORD_ARGON2ID);
    }

    private static function isValidHash(string $hash): bool
    {
        $info = password_get_info($hash);

        return $info['algo'] !== 0;
    }
}
