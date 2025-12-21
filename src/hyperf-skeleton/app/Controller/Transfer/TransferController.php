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

class TransferController
{
    public function __construct(
        private RequestInterface $request,
        private ResponseInterface $response,
        private TransferMoneyHandler $handler,
        private TransferControllerValidator $validator,
        private LoggerInterface $logger
    ) {}

    public function store()
    {
        $payerId = $this->request->input('payer_id');
        $payeeId = $this->request->input('payee_id');
        $amount =  $this->request->input('amount');

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
                    ]
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
        } catch (\Throwable $e) {
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
