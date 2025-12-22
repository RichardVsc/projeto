<?php

declare(strict_types=1);

namespace App\Application\UseCase\TransferMoney;

use App\Application\Service\TransactionManagerInterface;
use App\Application\UseCase\TransferMoney\Exception\UserNotFoundException;
use App\Domain\Money\Money;
use App\Domain\Repository\TransferRepositoryInterface;
use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\Service\AuthorizationServiceInterface;
use App\Domain\Transfer\Event\TransferCompleted;
use App\Domain\Transfer\Transfer;
use App\Domain\Transfer\TransferId;
use App\Domain\Transfer\TransferRole;
use App\Domain\Transfer\TransferStatus;
use App\Domain\User\Exception\UserCannotSendMoneyException;
use App\Domain\User\Exception\UserInsufficientFundsException;
use App\Domain\User\User;
use App\Domain\User\UserId;
use Psr\EventDispatcher\EventDispatcherInterface;

final class TransferMoneyHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private TransferRepositoryInterface $transferRepository,
        private AuthorizationServiceInterface $authorizationService,
        private TransactionManagerInterface $transactionManager,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function handle(TransferMoneyCommand $command): TransferMoneyResponse
    {
        [$payer, $payee, $amount] = $this->prepareTransfer($command);
        $this->validateTransferRules($payer, $amount);
        $transfer = $this->createPendingTransfer($payer->getId(), $payee->getId(), $amount);

        $transfer = $this->authorizeTransfer($transfer);
        if ($transfer->getStatus() === TransferStatus::FAILED) {
            return $this->buildFailedResponse($transfer);
        }

        $transfer = $this->executeTransferWithLock($payer, $payee, $amount, $transfer);
        $this->eventDispatcher->dispatch(
            TransferCompleted::now($transfer)
        );

        return $this->buildSuccessResponse($transfer);
    }

    /**
     * @return array{User, User, Money}
     */
    private function prepareTransfer(TransferMoneyCommand $command): array
    {
        $payerId = UserId::fromString($command->payerId);
        $payeeId = UserId::fromString($command->payeeId);
        $amount = Money::fromCents($command->amountInCents);

        $payer = $this->findUser($payerId, TransferRole::PAYER);
        $payee = $this->findUser($payeeId, TransferRole::PAYEE);
        return [$payer, $payee, $amount];
    }

    private function findUser(UserId $userId, TransferRole $role): User
    {
        $user = $this->userRepository->findById($userId);

        if ($user === null) {
            throw match ($role) {
                TransferRole::PAYER => UserNotFoundException::payerNotFound($userId->getValue()),
                TransferRole::PAYEE => UserNotFoundException::payeeNotFound($userId->getValue()),
            };
        }

        return $user;
    }

    private function validateTransferRules(User $payer, Money $amount): void
    {
        if (! $payer->canSendMoney()) {
            throw UserCannotSendMoneyException::cannotSendMoney();
        }

        if (! $payer->hasSufficientBalance($amount)) {
            throw UserInsufficientFundsException::notEnoughBalance();
        }
    }

    private function createPendingTransfer(UserId $payerId, UserId $payeeId, Money $amount): Transfer
    {
        $transfer = Transfer::create(
            TransferId::generate(),
            $payerId,
            $payeeId,
            $amount
        );

        $this->transferRepository->save($transfer);

        return $transfer;
    }

    private function authorizeTransfer(Transfer $transfer): Transfer
    {
        $authorized = $this->authorizationService->authorize($transfer);

        if (! $authorized) {
            $failedTransfer = $transfer->fail('Authorization denied');
            $this->transferRepository->save($failedTransfer);
            return $failedTransfer;
        }

        $authorizedTransfer = $transfer->authorize();
        $this->transferRepository->save($authorizedTransfer);

        return $authorizedTransfer;
    }

    private function executeTransferWithLock(User $payer, User $payee, Money $amount, Transfer $transfer): Transfer
    {
        return $this->transactionManager->transaction(function () use ($payer, $payee, $amount, $transfer) {
            $users = $this->userRepository->findManyByIdsForUpdate([
                'payer' => $payer->getId(),
                'payee' => $payee->getId(),
            ]);

            $payer = $users['payer'] ?? throw UserNotFoundException::payerNotFound($payer->getId()->getValue());
            $payee = $users['payee'] ?? throw UserNotFoundException::payeeNotFound($payee->getId()->getValue());

            $this->validateTransferRules($payer, $amount);

            $updatedPayer = $payer->debitWallet($amount);
            $updatedPayee = $payee->creditWallet($amount);

            $this->userRepository->save($updatedPayer);
            $this->userRepository->save($updatedPayee);

            $completedTransfer = $transfer->complete();
            $this->transferRepository->save($completedTransfer);

            return $completedTransfer;
        });
    }

    private function buildFailedResponse(Transfer $transfer): TransferMoneyResponse
    {
        return new TransferMoneyResponse(
            $transfer->getId()->getValue(),
            $transfer->getStatus()->value,
            $transfer->getFailureReason()
        );
    }

    private function buildSuccessResponse(Transfer $transfer): TransferMoneyResponse
    {
        return new TransferMoneyResponse(
            $transfer->getId()->getValue(),
            $transfer->getStatus()->value,
            null
        );
    }
}
