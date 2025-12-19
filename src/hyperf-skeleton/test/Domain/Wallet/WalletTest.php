<?php

declare(strict_types=1);

namespace HyperfTest\Domain\Wallet;

use App\Domain\Money\Money;
use App\Domain\Wallet\Wallet;
use App\Domain\Wallet\WalletId;
use App\Domain\Wallet\WalletType;
use App\Domain\Wallet\Exception\NegativeWalletBalanceException;
use App\Domain\Wallet\Exception\InvalidCreditAmountException;
use App\Domain\Wallet\Exception\InvalidDebitAmountException;
use App\Domain\Wallet\Exception\WalletCannotSendException;
use App\Domain\Wallet\Exception\InsufficientBalanceException;
use PHPUnit\Framework\TestCase;

final class WalletTest extends TestCase
{
    public function test_it_can_be_created_with_valid_and_invalid_balances(): void
    {
        $wallet1 = new Wallet(new WalletId('wallet_test_1'), Money::fromCents(100), WalletType::USER);
        $this->assertSame(100, $wallet1->getBalance()->toInt());

        $wallet2 = new Wallet(new WalletId('wallet_test_2'), Money::fromCents(0), WalletType::USER);
        $this->assertSame(0, $wallet2->getBalance()->toInt());

        $this->expectException(NegativeWalletBalanceException::class);
        new Wallet(new WalletId('wallet_test_3'), Money::fromCents(-10), WalletType::USER);

        $wallet3 = new Wallet(new WalletId('wallet_test_4'), Money::fromCents(50), WalletType::MERCHANT);
        $this->assertSame(50, $wallet3->getBalance()->toInt());
    }

    public function test_canSendMoney_returns_correct_value(): void
    {
        $walletUser = new Wallet(new WalletId('wallet_test_5'), Money::fromCents(100), WalletType::USER);
        $this->assertTrue($walletUser->canSendMoney());

        $walletMerchant = new Wallet(new WalletId('wallet_test_6'), Money::fromCents(100), WalletType::MERCHANT);
        $this->assertFalse($walletMerchant->canSendMoney());
    }

    public function test_credit_adds_money(): void
    {
        $wallet = new Wallet(new WalletId('wallet_test_7'), Money::fromCents(100), WalletType::USER);

        $wallet->credit(Money::fromCents(50));
        $this->assertSame(150, $wallet->getBalance()->toInt());
    }

    public function test_credit_zero_throws_exception(): void
    {
        $wallet = new Wallet(new WalletId('wallet_test_8'), Money::fromCents(100), WalletType::USER);
        $this->expectException(InvalidCreditAmountException::class);
        $wallet->credit(Money::fromCents(0));
    }

    public function test_credit_negative_throws_exception(): void
    {
        $wallet = new Wallet(new WalletId('wallet_test_9'), Money::fromCents(100), WalletType::USER);
        $this->expectException(InvalidCreditAmountException::class);
        $wallet->credit(Money::fromCents(-10));
    }

    public function test_debit_subtracts_money(): void
    {
        $wallet = new Wallet(new WalletId('wallet_test_10'), Money::fromCents(100), WalletType::USER);
        $wallet->debit(Money::fromCents(50));
        $this->assertSame(50, $wallet->getBalance()->toInt());
    }

    public function test_debit_zero_throws_exception(): void
    {
        $wallet = new Wallet(new WalletId('wallet_test_11'), Money::fromCents(100), WalletType::USER);
        $this->expectException(InvalidDebitAmountException::class);
        $wallet->debit(Money::fromCents(0));
    }

    public function test_debit_negative_throws_exception(): void
    {
        $wallet = new Wallet(new WalletId('wallet_test_12'), Money::fromCents(100), WalletType::USER);
        $this->expectException(InvalidDebitAmountException::class);
        $wallet->debit(Money::fromCents(-10));
    }

    public function test_debit_insufficient_balance_throws_exception(): void
    {
        $wallet = new Wallet(new WalletId('wallet_test_13'), Money::fromCents(50), WalletType::USER);
        $this->expectException(InsufficientBalanceException::class);
        $wallet->debit(Money::fromCents(100));
    }

    public function test_debit_merchant_wallet_cannot_send(): void
    {
        $wallet = new Wallet(new WalletId('wallet_test_14'), Money::fromCents(100), WalletType::MERCHANT);
        $this->expectException(WalletCannotSendException::class);
        $wallet->debit(Money::fromCents(10));
    }

    public function test_credit_and_debit_sequence_preserves_integrity(): void
    {
        $wallet = new Wallet(new WalletId('wallet_test_15'), Money::fromCents(100), WalletType::USER);

        $wallet->credit(Money::fromCents(50));
        $this->assertSame(150, $wallet->getBalance()->toInt());

        $wallet->debit(Money::fromCents(70));
        $this->assertSame(80, $wallet->getBalance()->toInt());

        $wallet2 = new Wallet(new WalletId('wallet_test_16'), Money::fromCents(0), WalletType::USER);
        $wallet2->credit(Money::fromCents(20));
        $this->assertSame(20, $wallet2->getBalance()->toInt());

        $this->expectException(InsufficientBalanceException::class);
        $wallet2->debit(Money::fromCents(25));
    }

    public function test_invariants_are_maintained(): void
    {
        $wallet = new Wallet(new WalletId('wallet_test_17'), Money::fromCents(100), WalletType::USER);
        $originalId = $wallet->getId();

        $wallet->credit(Money::fromCents(50));
        $wallet->debit(Money::fromCents(30));

        $this->assertSame($originalId, $wallet->getId());
        $this->assertInstanceOf(Money::class, $wallet->getBalance());
        $this->assertTrue($wallet->canSendMoney());
    }

    public function test_limits_for_credit_and_debit(): void
    {
        $wallet = new Wallet(new WalletId('wallet_test_18'), Money::fromCents(PHP_INT_MAX - 1), WalletType::USER);

        $wallet->credit(Money::fromCents(1));
        $this->assertSame(PHP_INT_MAX, $wallet->getBalance()->toInt());

        $wallet->debit(Money::fromCents(PHP_INT_MAX));
        $this->assertSame(0, $wallet->getBalance()->toInt());
    }
}
