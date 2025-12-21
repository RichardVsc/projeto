<?php

declare(strict_types=1);

namespace HyperfTest\Integration;

/**
 * Integration tests for the Transfer endpoint.
 *
 * @internal
 * @coversNothing
 */
final class TransferTest extends IntegrationTestCase
{
    /**
     * @test
     * Test 1: User COMMON can transfer to MERCHANT successfully.
     */
    public function it_should_transfer_successfully_from_user_to_merchant(): void
    {
        $payload = [
            'payer_id' => self::USER_JOAO_ID,
            'payee_id' => self::USER_LOJA_ID,
            'amount' => 10000
        ];

        $initialPayerBalance = $this->getWalletBalance(self::USER_JOAO_ID);
        $initialPayeeBalance = $this->getWalletBalance(self::USER_LOJA_ID);

        $response = $this->postTransfer($payload);

        $response->assertCreated();
        $response->assertJson(['status' => 'completed']);
        $response->assertJsonStructure([
            'status',
            'data' => ['transfer_id', 'payer_id', 'payee_id', 'amount'],
        ]);

        $this->assertDatabaseHas('transfers', [
            'payer_id' => $payload['payer_id'],
            'payee_id' => $payload['payee_id'],
            'amount' => $payload['amount'],
            'status' => 'completed',
        ]);

        $this->assertEquals(
            $initialPayerBalance - $payload['amount'],
            $this->getWalletBalance(self::USER_JOAO_ID),
            'Payer balance should be debited'
        );

        $this->assertEquals(
            $initialPayeeBalance + $payload['amount'],
            $this->getWalletBalance(self::USER_LOJA_ID),
            'Payee balance should be credited'
        );
    }

    /**
     * @test
     * Test 2: User COMMON can transfer to another User COMMON successfully.
     */
    public function it_should_transfer_successfully_from_user_to_user(): void
    {
        $payload = [
            'payer_id' => self::USER_JOAO_ID,
            'payee_id' => self::USER_MARIA_ID,
            'amount' => 5000,
        ];

        $initialPayerBalance = $this->getWalletBalance(self::USER_JOAO_ID);
        $initialPayeeBalance = $this->getWalletBalance(self::USER_MARIA_ID);

        $response = $this->postTransfer($payload);

        $response->assertCreated();
        $response->assertJson(['status' => 'completed']);

        $this->assertEquals(
            $initialPayerBalance - $payload['amount'],
            $this->getWalletBalance(self::USER_JOAO_ID)
        );

        $this->assertEquals(
            $initialPayeeBalance + $payload['amount'],
            $this->getWalletBalance(self::USER_MARIA_ID)
        );
    }

    /**
     * @test
     * Test 3: MERCHANT cannot send money.
     */
    public function it_should_fail_when_merchant_tries_to_send_money(): void
    {
        $payload = [
            'payer_id' => self::USER_LOJA_ID,
            'payee_id' => self::USER_JOAO_ID,
            'amount' => 5000,
        ];

        $initialPayerBalance = $this->getWalletBalance(self::USER_LOJA_ID);
        $initialPayeeBalance = $this->getWalletBalance(self::USER_JOAO_ID);
        $initialTransferCount = $this->getTransferCount();

        $response = $this->postTransfer($payload);

        $response->assertForbidden();
        $response->assertJson(['status' => 'failed']);

        $this->assertEquals(
            $initialTransferCount,
            $this->getTransferCount(),
            'No transfer should be created'
        );

        $this->assertEquals(
            $initialPayerBalance,
            $this->getWalletBalance(self::USER_LOJA_ID),
            'Payer balance should NOT change'
        );

        $this->assertEquals(
            $initialPayeeBalance,
            $this->getWalletBalance(self::USER_JOAO_ID),
            'Payee balance should NOT change'
        );
    }

    /**
     * @test
     * Test 4: User with insufficient funds cannot transfer.
     */
    public function it_should_fail_when_user_has_insufficient_funds(): void
    {
        $payload = [
            'payer_id' => self::USER_PEDRO_ID,
            'payee_id' => self::USER_JOAO_ID,
            'amount' => 1000,
        ];

        $initialTransferCount = $this->getTransferCount();

        $response = $this->postTransfer($payload);

        $response->assertUnprocessable();
        $response->assertJson(['status' => 'failed']);

        $this->assertEquals($initialTransferCount, $this->getTransferCount());

        $this->assertEquals(0, $this->getWalletBalance(self::USER_PEDRO_ID));
    }

    /**
     * @test
     * Test 5: Transfer fails when payer does not exist.
     */
    public function it_should_fail_when_payer_not_found(): void
    {
        $payload = [
            'payer_id' => self::USER_INEXISTENTE_ID,
            'payee_id' => self::USER_JOAO_ID,
            'amount' => 1000,
        ];

        $response = $this->postTransfer($payload);

        $response->assertNotFound();
        $response->assertJson(['status' => 'failed']);
    }

    /**
     * @test
     * Test 6: Transfer fails when payee does not exist.
     */
    public function it_should_fail_when_payee_not_found(): void
    {
        $payload = [
            'payer_id' => self::USER_JOAO_ID,
            'payee_id' => self::USER_INEXISTENTE_ID,
            'amount' => 1000,
        ];

        $response = $this->postTransfer($payload);

        $response->assertNotFound();
        $response->assertJson(['status' => 'failed']);
    }

    /**
     * @test
     * Test 7: User cannot transfer to themselves.
     */
    public function it_should_fail_when_transferring_to_self(): void
    {
        $payload = [
            'payer_id' => self::USER_JOAO_ID,
            'payee_id' => self::USER_JOAO_ID,
            'amount' => 1000,
        ];

        $initialBalance = $this->getWalletBalance(self::USER_JOAO_ID);
        $initialTransferCount = $this->getTransferCount();

        $response = $this->postTransfer($payload);

        $response->assertUnprocessable();

        $this->assertEquals($initialTransferCount, $this->getTransferCount());

        $this->assertEquals($initialBalance, $this->getWalletBalance(self::USER_JOAO_ID));
    }

    /**
     * @test
     * Test 8: Transfer fails when external authorization is denied.
     */
    public function it_should_fail_when_authorization_is_denied(): void
    {
        $this->mockAuthorizationService(false);

        $payload = [
            'payer_id' => self::USER_JOAO_ID,
            'payee_id' => self::USER_LOJA_ID,
            'amount' => 1000,
        ];

        $initialPayerBalance = $this->getWalletBalance(self::USER_JOAO_ID);
        $initialPayeeBalance = $this->getWalletBalance(self::USER_LOJA_ID);

        $handler = new \App\Application\UseCase\TransferMoney\TransferMoneyHandler(
            $this->container->get(\App\Domain\Repository\UserRepositoryInterface::class),
            $this->container->get(\App\Domain\Repository\TransferRepositoryInterface::class),
            $this->container->get(\App\Domain\Service\AuthorizationServiceInterface::class),
            $this->container->get(\App\Application\Service\TransactionManagerInterface::class),
            $this->container->get(\Psr\EventDispatcher\EventDispatcherInterface::class)
        );

        $command = new \App\Application\UseCase\TransferMoney\TransferMoneyCommand(
            payerId: $payload['payer_id'],
            payeeId: $payload['payee_id'],
            amountInCents: $payload['amount']
        );

        $result = $handler->handle($command);

        $response = \HyperfTest\Integration\TestResponse::fromArray(
            $result->isSuccessful()
                ? [
                    'status' => 'completed',
                    'data' => [
                        'transfer_id' => $result->getTransferId(),
                        'payer_id' => $payload['payer_id'],
                        'payee_id' => $payload['payee_id'],
                        'amount' => $payload['amount'],
                    ],
                ]
                : [
                    'status' => 'failed',
                    'transfer_id' => $result->getTransferId(),
                    'reason' => $result->getFailureReason(),
                ],
            $result->isSuccessful() ? 201 : 422
        );

        $response->assertUnprocessable();

        $transfer = $this->getLastTransfer();
        $this->assertNotNull($transfer);
        $this->assertEquals('failed', $transfer->status);
        $this->assertEquals('Authorization denied', $transfer->failure_reason);

        $this->assertEquals($initialPayerBalance, $this->getWalletBalance(self::USER_JOAO_ID));
        $this->assertEquals($initialPayeeBalance, $this->getWalletBalance(self::USER_LOJA_ID));
    }



    /**
     * @test
     * Test 9: Validation fails when amount is zero.
     */
    public function it_should_fail_validation_when_amount_is_zero(): void
    {
        $payload = [
            'payer_id' => self::USER_JOAO_ID,
            'payee_id' => self::USER_LOJA_ID,
            'amount' => 0,
        ];

        $response = $this->postTransfer($payload);

        $response->assertBadRequest();
    }

    /**
     * @test
     * Test 10: Validation fails when UUID is invalid.
     */
    public function it_should_fail_validation_when_uuid_is_invalid(): void
    {
        $payload = [
            'payer_id' => 'invalid-uuid',
            'payee_id' => self::USER_LOJA_ID,
            'amount' => 1000,
        ];

        $response = $this->postTransfer($payload);

        $response->assertBadRequest();
    }
}
