<?php

declare(strict_types=1);

namespace HyperfTest\Unit\Domain\User;

use App\Domain\Money\Money;
use App\Domain\User\DocumentNumber;
use App\Domain\User\Email;
use App\Domain\User\Exception\InvalidUserException;
use App\Domain\User\Exception\UserCannotSendMoneyException;
use App\Domain\User\Exception\UserInsufficientFundsException;
use App\Domain\User\HashedPassword;
use App\Domain\User\User;
use App\Domain\User\UserId;
use App\Domain\User\UserType;
use App\Domain\User\Wallet;
use App\Domain\User\WalletId;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class UserTest extends TestCase
{
    public function testCanCreateValidUser(): void
    {
        $user = $this->createCommonUser();

        $this->assertSame('John Doe', $user->getName());
        $this->assertTrue($user->canSendMoney());
        $this->assertTrue($user->verifyPassword('securePassword'));
    }

    public function testCreationFailsWhenNameIsEmpty(): void
    {
        $this->expectException(InvalidUserException::class);

        new User(
            UserId::generate(),
            UserType::COMMON,
            '   ',
            DocumentNumber::cpf('111.444.777-35'),
            Email::fromString('john@example.com'),
            HashedPassword::fromPlainText('securePassword'),
            $this->createWalletWithBalance(0)
        );
    }

    public function testVerifyPasswordReturnsFalseForIncorrectPassword(): void
    {
        $user = $this->createCommonUser();

        $this->assertFalse($user->verifyPassword('wrongPassword'));
    }

    public function testUserCannotSendMoneyWhenTypeDisallows(): void
    {
        $user = new User(
            UserId::generate(),
            UserType::MERCHANT,
            'Shop Owner',
            DocumentNumber::cnpj('45.723.174/0001-10'),
            Email::fromString('shop@example.com'),
            HashedPassword::fromPlainText('securePassword'),
            $this->createWalletWithBalance(100)
        );

        $this->assertFalse($user->canSendMoney());
    }

    public function testCanCreditWallet(): void
    {
        $user = $this->createCommonUser();

        $credited = $user->creditWallet(Money::fromCents(100));

        $this->assertSame(0, $user->getWallet()->getBalance()->toInt());
        $this->assertSame(100, $credited->getWallet()->getBalance()->toInt());
    }

    public function testDebitWalletDeductsBalance(): void
    {
        $user = $this->createCommonUserWithBalance(200);

        $debited = $user->debitWallet(Money::fromCents(50));

        $this->assertSame(200, $user->getWallet()->getBalance()->toInt());
        $this->assertSame(150, $debited->getWallet()->getBalance()->toInt());
    }

    public function testDebitWalletFailsWhenUserCannotSendMoney(): void
    {
        $user = new User(
            UserId::generate(),
            UserType::MERCHANT,
            'Shop Owner',
            DocumentNumber::cnpj('45.723.174/0001-10'),
            Email::fromString('shop@example.com'),
            HashedPassword::fromPlainText('securePassword'),
            $this->createWalletWithBalance(100)
        );

        $this->expectException(UserCannotSendMoneyException::class);

        $user->debitWallet(Money::fromCents(10));
    }

    public function testDebitWalletFailsWhenInsufficientFunds(): void
    {
        $user = $this->createCommonUserWithBalance(20);

        $this->expectException(UserInsufficientFundsException::class);

        $user->debitWallet(Money::fromCents(50));
    }

    public function testUserIsImmutableWhenWalletChanges(): void
    {
        $user = $this->createCommonUser();

        $credited = $user->creditWallet(Money::fromCents(100));

        $this->assertNotSame($user, $credited);
        $this->assertSame(0, $user->getWallet()->getBalance()->toInt());
        $this->assertSame(100, $credited->getWallet()->getBalance()->toInt());
    }

    private function createCommonUser(): User
    {
        return new User(
            UserId::generate(),
            UserType::COMMON,
            'John Doe',
            DocumentNumber::cpf('111.444.777-35'),
            Email::fromString('john@example.com'),
            HashedPassword::fromPlainText('securePassword'),
            $this->createWalletWithBalance(0)
        );
    }

    private function createCommonUserWithBalance(int $cents): User
    {
        return new User(
            UserId::generate(),
            UserType::COMMON,
            'John Doe',
            DocumentNumber::cpf('111.444.777-35'),
            Email::fromString('john@example.com'),
            HashedPassword::fromPlainText('securePassword'),
            $this->createWalletWithBalance($cents)
        );
    }

    private function createWalletWithBalance(int $cents): Wallet
    {
        return new Wallet(
            WalletId::generate(),
            Money::fromCents($cents)
        );
    }
}
