<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repository;

use App\Application\UseCase\TransferMoney\Exception\UserNotFoundException;
use App\Domain\Money\Money;
use App\Domain\Repository\LockedUserPair;
use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\User\DocumentNumber;
use App\Domain\User\DocumentType;
use App\Domain\User\Email;
use App\Domain\User\HashedPassword;
use App\Domain\User\User;
use App\Domain\User\UserId;
use App\Domain\User\UserType;
use App\Domain\User\Wallet;
use App\Domain\User\WalletId;
use App\Infrastructure\Persistence\Exception\WalletNotFoundException;
use App\Infrastructure\Persistence\Model\UserModel;
use App\Infrastructure\Persistence\Model\WalletModel;

final class EloquentUserRepository implements UserRepositoryInterface
{
    public function findById(UserId $id): ?User
    {
        $userModel = UserModel::with('wallet')
            ->where('id', $id->getValue())
            ->first();

        if ($userModel === null) {
            return null;
        }

        return $this->hydrate($userModel);
    }

    public function findByIdForUpdate(UserId $id): ?User
    {
        $userModel = UserModel::with('wallet')
            ->where('id', $id->getValue())
            ->lockForUpdate()
            ->first();

        if ($userModel === null) {
            return null;
        }

        return $this->hydrate($userModel);
    }

    public function findPairForUpdate(UserId $payerId, UserId $payeeId): LockedUserPair
    {
        $ids = ['payer' => $payerId, 'payee' => $payeeId];
        uasort($ids, fn (UserId $userIdA, UserId $userIdB) => strcmp($userIdA->getValue(), $userIdB->getValue()));

        $users = [];
        foreach ($ids as $role => $id) {
            $users[$role] = $this->findByIdForUpdate($id);

            if ($users[$role] === null) {
                throw match ($role) {
                    'payer' => UserNotFoundException::payerNotFound($id->getValue()),
                    'payee' => UserNotFoundException::payeeNotFound($id->getValue()),
                };
            }
        }

        return new LockedUserPair($users['payer'], $users['payee']);
    }

    public function save(User $user): void
    {
        UserModel::updateOrCreate(
            ['id' => $user->getId()->getValue()],
            [
                'type' => $user->getType()->value,
                'name' => $user->getName(),
                'document_number' => $user->getDocument()->getValue(),
                'document_type' => $user->getDocument()->getType()->value,
                'email' => $user->getEmail()->getValue(),
                'password' => $user->getPassword()->getHash(),
            ]
        );

        WalletModel::updateOrCreate(
            ['id' => $user->getWallet()->getId()->getValue()],
            [
                'user_id' => $user->getId()->getValue(),
                'balance' => $user->getWallet()->getBalance()->toInt(),
            ]
        );
    }

    private function hydrate(UserModel $userModel): User
    {
        if ($userModel->wallet === null) {
            throw WalletNotFoundException::forUser($userModel->id);
        }

        $documentType = DocumentType::from($userModel->document_type);

        $document = match ($documentType) {
            DocumentType::CPF => DocumentNumber::cpf($userModel->document_number),
            DocumentType::CNPJ => DocumentNumber::cnpj($userModel->document_number),
        };

        $wallet = new Wallet(
            WalletId::fromString($userModel->wallet->id),
            Money::fromCents($userModel->wallet->balance)
        );

        return new User(
            UserId::fromString($userModel->id),
            UserType::from($userModel->type),
            $userModel->name,
            $document,
            Email::fromString($userModel->email),
            HashedPassword::fromHash($userModel->password),
            $wallet
        );
    }
}
