<?php

declare(strict_types=1);

namespace HyperfTest\Unit\Domain\User;

use App\Domain\User\Email;
use App\Domain\User\Exception\InvalidEmailException;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class EmailTest extends TestCase
{
    public function testCanBeCreatedFromString(): void
    {
        $email = Email::fromString('Test@Example.com');

        $this->assertSame('test@example.com', $email->getValue());
        $this->assertSame('test@example.com', (string) $email);
    }

    public function testEmailIsNormalizedToLowercase(): void
    {
        $email = Email::fromString('USER@DOMAIN.COM');

        $this->assertSame('user@domain.com', $email->getValue());
    }

    public function testEmailTrimsWhitespace(): void
    {
        $email = Email::fromString('  test@example.com  ');

        $this->assertSame('test@example.com', $email->getValue());
    }

    public function testCreationFailsWhenEmailIsEmpty(): void
    {
        $this->expectException(InvalidEmailException::class);

        Email::fromString('   ');
    }

    public function testCreationFailsWhenEmailIsInvalid(): void
    {
        $this->expectException(InvalidEmailException::class);

        Email::fromString('invalid-email');
    }

    public function testEqualsReturnsTrueForSameEmail(): void
    {
        $email1 = Email::fromString('Test@Example.com');
        $email2 = Email::fromString('test@example.com');

        $this->assertTrue($email1->equals($email2));
    }

    public function testEqualsReturnsFalseForDifferentEmails(): void
    {
        $email1 = Email::fromString('test@example.com');
        $email2 = Email::fromString('other@example.com');

        $this->assertFalse($email1->equals($email2));
    }

    public function testEmailIsImmutable(): void
    {
        $email = Email::fromString('test@example.com');

        $this->assertSame('test@example.com', $email->getValue());
    }
}
