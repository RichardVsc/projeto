<?php

declare(strict_types=1);

namespace HyperfTest\Integration;

use App\Application\UseCase\TransferMoney\Exception\UserNotFoundException;
use App\Application\UseCase\TransferMoney\TransferMoneyCommand;
use App\Application\UseCase\TransferMoney\TransferMoneyHandler;
use App\Domain\Service\AuthorizationServiceInterface;
use App\Domain\Service\NotificationServiceInterface;
use App\Domain\Transfer\Exception\InvalidTransferException;
use App\Domain\User\Exception\InvalidUserIdException;
use App\Domain\User\Exception\UserCannotSendMoneyException;
use App\Domain\User\Exception\UserInsufficientFundsException;
use App\Validators\Exception\ValidationException;
use App\Validators\Transfer\TransferControllerValidator;
use Hyperf\Context\ApplicationContext;
use Hyperf\Contract\ApplicationInterface;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Container;
use Mockery;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Throwable;

/**
 * Base class for integration tests.
 * Provides database setup, mocking helpers, and controller invocation.
 */
abstract class IntegrationTestCase extends TestCase
{
    /**
     * Test user UUIDs from seeders.
     */
    protected const USER_JOAO_ID = '550e8400-e29b-41d4-a716-446655440001';

    protected const USER_LOJA_ID = '550e8400-e29b-41d4-a716-446655440002';

    protected const USER_MARIA_ID = '550e8400-e29b-41d4-a716-446655440003';

    protected const USER_PEDRO_ID = '550e8400-e29b-41d4-a716-446655440004';

    protected const USER_INEXISTENTE_ID = '550e8400-e29b-41d4-a716-999999999999';

    /**
     * Initial balances from seeders (in cents).
     */
    protected const BALANCE_JOAO = 100000;

    protected const BALANCE_LOJA = 50000;

    protected const BALANCE_MARIA = 20000;

    protected const BALANCE_PEDRO = 0;

    protected ContainerInterface $container;

    protected function setUp(): void
    {
        parent::setUp();

        $this->container = ApplicationContext::getContainer();

        $this->refreshDatabase();

        $this->setupDefaultMocks();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Refresh the database with migrations and seeders.
     */
    protected function refreshDatabase(): void
    {
        $this->runCommand('migrate:fresh');
        $this->runCommand('db:seed');
    }

    /**
     * Run a Hyperf console command.
     */
    protected function runCommand(string $command): void
    {
        /** @var Application $application */
        $application = $this->container->get(ApplicationInterface::class);

        $input = new ArrayInput([
            'command' => $command,
            '--force' => true,
        ]);
        $output = new NullOutput();

        $application->setAutoExit(false);
        $application->run($input, $output);
    }

    /**
     * Setup default mocks for external services.
     */
    protected function setupDefaultMocks(): void
    {
        $this->mockAuthorizationService(true);
        $this->mockNotificationService();
    }

    /**
     * Mock the AuthorizationService.
     */
    protected function mockAuthorizationService(bool $shouldAuthorize): void
    {
        $mock = Mockery::mock(AuthorizationServiceInterface::class);
        $mock->shouldReceive('authorize')
            ->andReturn($shouldAuthorize);

        $this->rebindInContainer(AuthorizationServiceInterface::class, $mock);
    }

    /**
     * Mock the NotificationService.
     */
    protected function mockNotificationService(): void
    {
        $mock = Mockery::mock(NotificationServiceInterface::class);
        $mock->shouldReceive('notify')
            ->andReturnNull();

        $this->rebindInContainer(NotificationServiceInterface::class, $mock);
    }

    /**
     * Rebind a service in the container.
     *
     * @param string $abstract The interface/class name
     * @param mixed $concrete The instance to bind
     */
    protected function rebindInContainer(string $abstract, mixed $concrete): void
    {
        /** @var Container $container */
        $container = $this->container;
        $container->set($abstract, $concrete);
    }

    /**
     * Make a POST request to /transfer endpoint.
     *
     * Instead of calling the controller directly (which requires HTTP context),
     * we call the handler and validator directly and simulate the controller logic.
     *
     * @param array $payload The transfer payload
     */
    protected function postTransfer(array $payload): TestResponse
    {
        $payerId = $payload['payer_id'] ?? null;
        $payeeId = $payload['payee_id'] ?? null;
        $amount = $payload['amount'] ?? null;

        try {
            $validator = $this->container->get(TransferControllerValidator::class);
            $validator->validate($payerId, $payeeId, $amount);

            $command = new TransferMoneyCommand(
                payerId: (string) $payerId,
                payeeId: (string) $payeeId,
                amountInCents: (int) $amount,
            );

            $handler = $this->container->get(TransferMoneyHandler::class);
            $result = $handler->handle($command);

            if ($result->isSuccessful()) {
                return TestResponse::fromArray([
                    'status' => 'completed',
                    'data' => [
                        'transfer_id' => $result->getTransferId(),
                        'payer_id' => $payerId,
                        'payee_id' => $payeeId,
                        'amount' => $amount,
                    ],
                ], 201);
            }

            return TestResponse::fromArray([
                'status' => 'failed',
                'transfer_id' => $result->getTransferId(),
                'reason' => $result->getFailureReason(),
            ], 422);
        } catch (UserNotFoundException $e) {
            return TestResponse::fromArray([
                'status' => 'failed',
                'error' => $e->getMessage(),
            ], 404);
        } catch (InvalidUserIdException $e) {
            return TestResponse::fromArray([
                'status' => 'failed',
                'error' => $e->getMessage(),
            ], 400);
        } catch (UserCannotSendMoneyException $e) {
            return TestResponse::fromArray([
                'status' => 'failed',
                'error' => $e->getMessage(),
            ], 403);
        } catch (UserInsufficientFundsException $e) {
            return TestResponse::fromArray([
                'status' => 'failed',
                'error' => $e->getMessage(),
            ], 422);
        } catch (InvalidTransferException $e) {
            return TestResponse::fromArray([
                'status' => 'failed',
                'error' => $e->getMessage(),
            ], 422);
        } catch (ValidationException $e) {
            return TestResponse::fromArray([
                'error' => $e->getErrors(),
            ], 400);
        } catch (Throwable $e) {
            return TestResponse::fromArray([
                'status' => 'failed',
                'error' => 'Internal server error',
                'debug' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Assert that a record exists in the database.
     */
    protected function assertDatabaseHas(string $table, array $data): void
    {
        $query = Db::table($table);

        foreach ($data as $column => $value) {
            $query->where($column, $value);
        }

        $this->assertTrue(
            $query->exists(),
            sprintf(
                'Failed asserting that table [%s] has record matching: %s',
                $table,
                json_encode($data)
            )
        );
    }

    /**
     * Assert that a record does NOT exist in the database.
     */
    protected function assertDatabaseMissing(string $table, array $data): void
    {
        $query = Db::table($table);

        foreach ($data as $column => $value) {
            $query->where($column, $value);
        }

        $this->assertFalse(
            $query->exists(),
            sprintf(
                'Failed asserting that table [%s] does NOT have record matching: %s',
                $table,
                json_encode($data)
            )
        );
    }

    /**
     * Assert the count of records in a table.
     */
    protected function assertDatabaseCount(string $table, int $expectedCount, array $where = []): void
    {
        $query = Db::table($table);

        foreach ($where as $column => $value) {
            $query->where($column, $value);
        }

        $actualCount = $query->count();

        $this->assertEquals(
            $expectedCount,
            $actualCount,
            sprintf(
                'Failed asserting that table [%s] has %d records. Actual: %d',
                $table,
                $expectedCount,
                $actualCount
            )
        );
    }

    /**
     * Get the balance of a user's wallet.
     */
    protected function getWalletBalance(string $userId): int
    {
        $wallet = Db::table('wallets')
            ->where('user_id', $userId)
            ->first();

        return $wallet ? (int) $wallet->balance : 0;
    }

    /**
     * Get user data by ID.
     */
    protected function getUser(string $userId): ?object
    {
        return Db::table('users')
            ->where('id', $userId)
            ->first();
    }

    /**
     * Get the last transfer from the database.
     */
    protected function getLastTransfer(): ?object
    {
        return Db::table('transfers')
            ->orderBy('created_at', 'desc')
            ->first();
    }

    /**
     * Get transfer count.
     */
    protected function getTransferCount(): int
    {
        return Db::table('transfers')->count();
    }
}
