<?php

declare(strict_types=1);

namespace HyperfTest\Unit\Domain\User;

use App\Domain\User\Exception\InvalidHashedPasswordException;
use App\Domain\User\HashedPassword;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class HashedPasswordTest extends TestCase
{
    public function testCanBeCreatedFromPlainText(): void
    {
        $password = HashedPassword::fromPlainText('securePassword');

        $this->assertNotEmpty($password->getHash());
        $this->assertTrue(password_get_info($password->getHash())['algo'] !== 0);
    }

    public function testPasswordIsHashedAndNotStoredInPlainText(): void
    {
        $plainText = 'securePassword';
        $password = HashedPassword::fromPlainText($plainText);

        $this->assertNotSame($plainText, $password->getHash());
    }

    public function testVerifyReturnsTrueForCorrectPassword(): void
    {
        $password = HashedPassword::fromPlainText('securePassword');

        $this->assertTrue($password->verify('securePassword'));
    }

    public function testVerifyReturnsFalseForIncorrectPassword(): void
    {
        $password = HashedPassword::fromPlainText('securePassword');

        $this->assertFalse($password->verify('wrongPassword'));
    }

    public function testCanBeCreatedFromExistingHash(): void
    {
        $hash = password_hash('securePassword', PASSWORD_ARGON2ID);

        $password = HashedPassword::fromHash($hash);

        $this->assertSame($hash, $password->getHash());
        $this->assertTrue($password->verify('securePassword'));
    }

    public function testCreationFromHashFailsWhenInvalid(): void
    {
        $this->expectException(InvalidHashedPasswordException::class);

        HashedPassword::fromHash('');
    }

    public function testCreationFromPlainTextFailsWhenEmpty(): void
    {
        $this->expectException(InvalidHashedPasswordException::class);

        HashedPassword::fromPlainText('');
    }

    public function testCreationFromPlainTextFailsWhenTooShort(): void
    {
        $this->expectException(InvalidHashedPasswordException::class);

        HashedPassword::fromPlainText('short');
    }

    public function testHashedPasswordIsImmutable(): void
    {
        $password = HashedPassword::fromPlainText('securePassword');

        $this->assertSame($password->getHash(), $password->getHash());
    }
}
