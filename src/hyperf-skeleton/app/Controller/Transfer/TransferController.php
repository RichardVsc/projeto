<?php

declare(strict_types=1);

namespace App\Controller\Transfer;

use App\Application\UseCase\TransferMoney\Exception\UserNotFoundException;
use App\Application\UseCase\TransferMoney\TransferMoneyCommand;
use App\Application\UseCase\TransferMoney\TransferMoneyHandler;
use App\Domain\Transfer\Exception\InvalidTransferException;
use App\Domain\User\Exception\InvalidUserIdException;
use App\Domain\User\Exception\UserCannotSendMoneyException;
use App\Domain\User\Exception\UserInsufficientFundsException;
use App\Validators\Exception\ValidationException;
use App\Validators\Transfer\TransferControllerValidator;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Psr\Log\LoggerInterface;
use Throwable;
use OpenApi\Attributes as OA;

class TransferController
{
    public function __construct(
        private RequestInterface $request,
        private ResponseInterface $response,
        private TransferMoneyHandler $handler,
        private TransferControllerValidator $validator,
        private LoggerInterface $logger
    ) {}

    #[OA\Post(
        path: '/transfer',
        summary: 'Realizar transferência de dinheiro',
        description: 'Transfere um valor em centavos de um usuário (payer) para outro (payee). Usuários do tipo Common podem enviar dinheiro, enquanto Merchants apenas recebem.',
        tags: ['Transfers'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['payer_id', 'payee_id', 'amount'],
                properties: [
                    new OA\Property(
                        property: 'payer_id',
                        type: 'string',
                        format: 'uuid',
                        example: '550e8400-e29b-41d4-a716-446655440001',
                        description: 'UUID do pagador (deve ser um usuário do tipo Common)'
                    ),
                    new OA\Property(
                        property: 'payee_id',
                        type: 'string',
                        format: 'uuid',
                        example: '550e8400-e29b-41d4-a716-446655440002',
                        description: 'UUID do beneficiário (pode ser Common ou Merchant)'
                    ),
                    new OA\Property(
                        property: 'amount',
                        type: 'integer',
                        minimum: 1,
                        example: 10000,
                        description: 'Valor em centavos (deve ser maior que zero)'
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Transferência realizada com sucesso',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'completed'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'transfer_id', type: 'string', format: 'uuid', example: '123e4567-e89b-12d3-a456-426614174000'),
                                new OA\Property(property: 'payer_id', type: 'string', format: 'uuid', example: '550e8400-e29b-41d4-a716-446655440001'),
                                new OA\Property(property: 'payee_id', type: 'string', format: 'uuid', example: '550e8400-e29b-41d4-a716-446655440002'),
                                new OA\Property(property: 'amount', type: 'integer', example: 10000),
                            ]
                        ),
                    ],
                    example: [
                        'status' => 'completed',
                        'data' => [
                            'transfer_id' => '123e4567-e89b-12d3-a456-426614174000',
                            'payer_id' => '550e8400-e29b-41d4-a716-446655440001',
                            'payee_id' => '550e8400-e29b-41d4-a716-446655440002',
                            'amount' => 10000,
                        ],
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Erro de validação (UUID inválido ou amount inválido)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'failed'),
                        new OA\Property(
                            property: 'error',
                            oneOf: [
                                new OA\Schema(type: 'string', example: 'Invalid user ID format'),
                                new OA\Schema(
                                    type: 'array',
                                    items: new OA\Items(type: 'string'),
                                    example: ['The payer_id must be a valid UUID.']
                                ),
                            ]
                        ),
                    ],
                    examples: [
                        new OA\Examples(
                            example: 'invalid_uuid',
                            summary: 'UUID inválido',
                            value: [
                                'status' => 'failed',
                                'error' => ['The payer_id must be a valid UUID.'],
                            ]
                        ),
                        new OA\Examples(
                            example: 'invalid_amount',
                            summary: 'Amount inválido',
                            value: [
                                'status' => 'failed',
                                'error' => ['The amount must be greater than zero.'],
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Merchant tentando enviar dinheiro',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'failed'),
                        new OA\Property(property: 'error', type: 'string', example: 'This user type cannot send money.'),
                    ],
                    example: [
                        'status' => 'failed',
                        'error' => 'This user type cannot send money.',
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Usuário não encontrado',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'failed'),
                        new OA\Property(property: 'error', type: 'string', example: 'Payer 00000000-0000-0000-0000-000000000000 was not found.'),
                    ],
                    example: [
                        'status' => 'failed',
                        'error' => 'Payer 00000000-0000-0000-0000-000000000000 was not found.',
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Falha na transferência (saldo insuficiente, autorização negada ou transferência para si mesmo)',
                content: new OA\JsonContent(
                    oneOf: [
                        new OA\Schema(
                            properties: [
                                new OA\Property(property: 'status', type: 'string'),
                                new OA\Property(property: 'error', type: 'string'),
                            ],
                            type: 'object'
                        ),
                        new OA\Schema(
                            properties: [
                                new OA\Property(property: 'status', type: 'string'),
                                new OA\Property(property: 'transfer_id', type: 'string', format: 'uuid'),
                                new OA\Property(property: 'reason', type: 'string'),
                            ],
                            type: 'object'
                        ),
                    ],
                    examples: [
                        new OA\Examples(
                            example: 'insufficient_funds',
                            summary: 'Saldo insuficiente',
                            value: [
                                'status' => 'failed',
                                'error' => 'User does not have enough balance.',
                            ]
                        ),
                        new OA\Examples(
                            example: 'authorization_denied',
                            summary: 'Autorização negada',
                            value: [
                                'status' => 'failed',
                                'transfer_id' => '123e4567-e89b-12d3-a456-426614174000',
                                'reason' => 'Authorization denied.',
                            ]
                        ),
                        new OA\Examples(
                            example: 'transfer_to_self',
                            summary: 'Transferência para si mesmo',
                            value: [
                                'status' => 'failed',
                                'error' => 'Cannot transfer to self.',
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: 'Erro interno do servidor',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'failed'),
                        new OA\Property(property: 'error', type: 'string', example: 'Internal server error'),
                    ],
                    example: [
                        'status' => 'failed',
                        'error' => 'Internal server error',
                    ]
                )
            ),
        ]
    )]
    public function store()
    {
        $payerId = $this->request->input('payer_id');
        $payeeId = $this->request->input('payee_id');
        $amount = $this->request->input('amount');

        try {
            $this->validator->validate($payerId, $payeeId, $amount);
            $command = new TransferMoneyCommand(
                payerId: (string) $payerId,
                payeeId: (string) $payeeId,
                amountInCents: (int) $amount,
            );
            $result = $this->handler->handle($command);

            if ($result->isSuccessful()) {
                return $this->response->json([
                    'status' => 'completed',
                    'data' => [
                        'transfer_id' => $result->getTransferId(),
                        'payer_id' => $payerId,
                        'payee_id' => $payeeId,
                        'amount' => $amount,
                    ],
                ])->withStatus(201);
            }

            return $this->response->json([
                'status' => 'failed',
                'transfer_id' => $result->getTransferId(),
                'reason' => $result->getFailureReason(),
            ])->withStatus(422);
        } catch (UserNotFoundException $unf) {
            return $this->response->json([
                'status' => 'failed',
                'error' => $unf->getMessage(),
            ])->withStatus(404);
        } catch (InvalidUserIdException $iui) {
            return $this->response->json([
                'status' => 'failed',
                'error' => $iui->getMessage(),
            ])->withStatus(400);
        } catch (UserCannotSendMoneyException $ucsm) {
            return $this->response->json([
                'status' => 'failed',
                'error' => $ucsm->getMessage(),
            ])->withStatus(403);
        } catch (UserInsufficientFundsException $uif) {
            return $this->response->json([
                'status' => 'failed',
                'error' => $uif->getMessage(),
            ])->withStatus(422);
        } catch (InvalidTransferException $uif) {
            return $this->response->json([
                'status' => 'failed',
                'error' => $uif->getMessage(),
            ])->withStatus(422);
        } catch (ValidationException $e) {
            return $this->response->json([
                'error' => $e->getErrors(),
            ])->withStatus(400);
        } catch (Throwable $e) {
            print_r($e->getMessage());
            $this->logger->error('Transfer failed', [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'payer_id' => $payerId ?? null,
                'payee_id' => $payeeId ?? null,
            ]);

            return $this->response->json([
                'status' => 'failed',
                'error' => 'Internal server error',
            ])->withStatus(500);
        }
    }
}
