<?php

declare(strict_types=1);

namespace App\Tests\Controller\Transfer;

use App\Application\UseCase\TransferMoney\Exception\UserNotFoundException;
use App\Application\UseCase\TransferMoney\TransferMoneyCommand;
use App\Application\UseCase\TransferMoney\TransferMoneyHandlerInterface;
use App\Application\UseCase\TransferMoney\TransferMoneyResponse;
use App\Controller\Transfer\TransferController;
use App\Domain\Transfer\Exception\InvalidTransferException;
use App\Domain\Transfer\TransferStatus;
use App\Domain\User\Exception\InvalidUserIdException;
use App\Domain\User\Exception\UserCannotSendMoneyException;
use App\Domain\User\Exception\UserInsufficientFundsException;
use App\Validators\Exception\ValidationException;
use App\Validators\Transfer\TransferControllerValidatorInterface;
use Exception;
use Hyperf\Context\Context;
use Hyperf\HttpMessage\Base\Response;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Response as HyperfResponse;
use Mockery;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * @internal
 * @coversNothing
 */
class TransferControllerTest extends TestCase
{
    private Mockery\LegacyMockInterface|RequestInterface $request;

    private HyperfResponse $response;

    private HyperfResponse $responseClass;

    private Mockery\LegacyMockInterface|TransferMoneyHandlerInterface $handler;

    private Mockery\LegacyMockInterface|TransferControllerValidatorInterface $validator;

    private LoggerInterface|Mockery\LegacyMockInterface $logger;

    protected function setUp(): void
    {
        $this->request = Mockery::mock(RequestInterface::class);
        $this->response = $this->createResponseClass();
        $this->responseClass = $this->createResponseClass();
        $this->handler = Mockery::mock(TransferMoneyHandlerInterface::class);
        $this->validator = Mockery::mock(TransferControllerValidatorInterface::class);
        $this->logger = Mockery::mock(LoggerInterface::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testStoreWithValidDataShouldReturn201(): void
    {
        $payerId = '550e8400-e29b-41d4-a716-446655440001';
        $payeeId = '550e8400-e29b-41d4-a716-446655440002';
        $amount = 10000;
        $transferId = '123e4567-e89b-12d3-a456-426614174000';

        $this->mockRequestInputs($payerId, $payeeId, $amount);
        $this->validator->shouldReceive('validate')
            ->once()
            ->with($payerId, $payeeId, $amount);

        $result = new TransferMoneyResponse(
            transferId: $transferId,
            status: TransferStatus::COMPLETED->value
        );

        $this->handler->shouldReceive('handle')
            ->once()
            ->with(Mockery::type(TransferMoneyCommand::class))
            ->andReturn($result);

        $expected = $this->responseClass->json([
            'status' => 'completed',
            'data' => [
                'transfer_id' => $transferId,
                'payer_id' => $payerId,
                'payee_id' => $payeeId,
                'amount' => $amount,
            ],
        ])->withStatus(201);

        $response = $this->createController()->store();

        $this->assertEquals($expected->getStatusCode(), $response->getStatusCode());
        $this->assertEquals($expected->getBody()->getContents(), $response->getBody()->getContents());
    }

    public function testStoreWithFailedAuthorizationShouldReturn422(): void
    {
        $payerId = '550e8400-e29b-41d4-a716-446655440001';
        $payeeId = '550e8400-e29b-41d4-a716-446655440002';
        $amount = 10000;
        $transferId = '123e4567-e89b-12d3-a456-426614174000';
        $failureReason = 'Authorization denied.';

        $this->mockRequestInputs($payerId, $payeeId, $amount);
        $this->validator->shouldReceive('validate')->once();

        $result = new TransferMoneyResponse(
            transferId: $transferId,
            status: TransferStatus::FAILED->value,
            failureReason: $failureReason
        );

        $this->handler->shouldReceive('handle')->once()->andReturn($result);

        $expected = $this->responseClass->json([
            'status' => 'failed',
            'transfer_id' => $transferId,
            'reason' => $failureReason,
        ])->withStatus(422);

        $response = $this->createController()->store();

        $this->assertEquals($expected->getStatusCode(), $response->getStatusCode());
        $this->assertEquals($expected->getBody()->getContents(), $response->getBody()->getContents());
    }

    public function testStoreWithUserNotFoundShouldReturn404(): void
    {
        $payerId = '00000000-0000-0000-0000-000000000000';
        $payeeId = '550e8400-e29b-41d4-a716-446655440002';
        $amount = 10000;
        $errorMessage = "Payer {$payerId} was not found.";

        $this->mockRequestInputs($payerId, $payeeId, $amount);
        $this->validator->shouldReceive('validate')->once();

        $this->handler->shouldReceive('handle')
            ->once()
            ->andThrow(new UserNotFoundException($errorMessage));

        $expected = $this->responseClass->json([
            'status' => 'failed',
            'error' => $errorMessage,
        ])->withStatus(404);

        $response = $this->createController()->store();

        $this->assertEquals($expected->getStatusCode(), $response->getStatusCode());
        $this->assertEquals($expected->getBody()->getContents(), $response->getBody()->getContents());
    }

    public function testStoreWithInvalidUserIdShouldReturn400(): void
    {
        $payerId = 'invalid-uuid';
        $payeeId = '550e8400-e29b-41d4-a716-446655440002';
        $amount = 10000;
        $errorMessage = 'Invalid user ID format';

        $this->mockRequestInputs($payerId, $payeeId, $amount);
        $this->validator->shouldReceive('validate')->once();

        $this->handler->shouldReceive('handle')
            ->once()
            ->andThrow(new InvalidUserIdException($errorMessage));

        $expected = $this->responseClass->json([
            'status' => 'failed',
            'error' => $errorMessage,
        ])->withStatus(400);

        $response = $this->createController()->store();

        $this->assertEquals($expected->getStatusCode(), $response->getStatusCode());
        $this->assertEquals($expected->getBody()->getContents(), $response->getBody()->getContents());
    }

    public function testStoreWithMerchantSendingMoneyShouldReturn403(): void
    {
        $payerId = '550e8400-e29b-41d4-a716-446655440001';
        $payeeId = '550e8400-e29b-41d4-a716-446655440002';
        $amount = 10000;
        $errorMessage = 'This user type cannot send money.';

        $this->mockRequestInputs($payerId, $payeeId, $amount);
        $this->validator->shouldReceive('validate')->once();

        $this->handler->shouldReceive('handle')
            ->once()
            ->andThrow(new UserCannotSendMoneyException($errorMessage));

        $expected = $this->responseClass->json([
            'status' => 'failed',
            'error' => $errorMessage,
        ])->withStatus(403);

        $response = $this->createController()->store();

        $this->assertEquals($expected->getStatusCode(), $response->getStatusCode());
        $this->assertEquals($expected->getBody()->getContents(), $response->getBody()->getContents());
    }

    public function testStoreWithInsufficientFundsShouldReturn422(): void
    {
        $payerId = '550e8400-e29b-41d4-a716-446655440001';
        $payeeId = '550e8400-e29b-41d4-a716-446655440002';
        $amount = 10000;
        $errorMessage = 'User does not have enough balance.';

        $this->mockRequestInputs($payerId, $payeeId, $amount);
        $this->validator->shouldReceive('validate')->once();

        $this->handler->shouldReceive('handle')
            ->once()
            ->andThrow(new UserInsufficientFundsException($errorMessage));

        $expected = $this->responseClass->json([
            'status' => 'failed',
            'error' => $errorMessage,
        ])->withStatus(422);

        $response = $this->createController()->store();

        $this->assertEquals($expected->getStatusCode(), $response->getStatusCode());
        $this->assertEquals($expected->getBody()->getContents(), $response->getBody()->getContents());
    }

    public function testStoreWithTransferToSelfShouldReturn422(): void
    {
        $payerId = '550e8400-e29b-41d4-a716-446655440001';
        $payeeId = '550e8400-e29b-41d4-a716-446655440001';
        $amount = 10000;
        $errorMessage = 'Cannot transfer to self.';

        $this->mockRequestInputs($payerId, $payeeId, $amount);
        $this->validator->shouldReceive('validate')->once();

        $this->handler->shouldReceive('handle')
            ->once()
            ->andThrow(new InvalidTransferException($errorMessage));

        $expected = $this->responseClass->json([
            'status' => 'failed',
            'error' => $errorMessage,
        ])->withStatus(422);

        $response = $this->createController()->store();

        $this->assertEquals($expected->getStatusCode(), $response->getStatusCode());
        $this->assertEquals($expected->getBody()->getContents(), $response->getBody()->getContents());
    }

    public function testStoreWithValidationErrorShouldReturn400(): void
    {
        $payerId = 'invalid-id';
        $payeeId = '550e8400-e29b-41d4-a716-446655440002';
        $amount = 100;
        $errors = ['The payer_id must be a valid UUID.'];

        $this->mockRequestInputs($payerId, $payeeId, $amount);

        $this->validator->shouldReceive('validate')
            ->once()
            ->andThrow(ValidationException::fromErrors($errors));

        $expected = $this->responseClass->json([
            'error' => $errors,
        ])->withStatus(400);

        $response = $this->createController()->store();

        $this->assertEquals($expected->getStatusCode(), $response->getStatusCode());
        $this->assertEquals($expected->getBody()->getContents(), $response->getBody()->getContents());
    }

    public function testStoreWithUnexpectedErrorShouldReturn500(): void
    {
        $payerId = '550e8400-e29b-41d4-a716-446655440001';
        $payeeId = '550e8400-e29b-41d4-a716-446655440002';
        $amount = 10000;

        $this->mockRequestInputs($payerId, $payeeId, $amount);
        $this->validator->shouldReceive('validate')->once();

        $this->handler->shouldReceive('handle')
            ->once()
            ->andThrow(new Exception('Database connection failed'));

        $this->logger->shouldReceive('error')
            ->once()
            ->with('Transfer failed', Mockery::type('array'));

        $expected = $this->responseClass->json([
            'status' => 'failed',
            'error' => 'Internal server error',
        ])->withStatus(500);

        ob_start();
        $response = $this->createController()->store();
        ob_end_clean();

        $this->assertEquals($expected->getStatusCode(), $response->getStatusCode());
        $this->assertEquals($expected->getBody()->getContents(), $response->getBody()->getContents());
    }

    private function mockRequestInputs(?string $payerId, ?string $payeeId, mixed $amount): void
    {
        $this->request->shouldReceive('input')
            ->with('payer_id')
            ->once()
            ->andReturn($payerId);

        $this->request->shouldReceive('input')
            ->with('payee_id')
            ->once()
            ->andReturn($payeeId);

        $this->request->shouldReceive('input')
            ->with('amount')
            ->once()
            ->andReturn($amount);
    }

    private function createResponseClass(): HyperfResponse
    {
        $psrResponse = new Response();
        Context::set(PsrResponseInterface::class, $psrResponse);
        return new HyperfResponse();
    }

    private function createController(): TransferController
    {
        return new TransferController(
            $this->request,
            $this->response,
            $this->handler,
            $this->validator,
            $this->logger
        );
    }
}
