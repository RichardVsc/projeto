<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\User\User;
use App\Domain\User\UserId;

interface UserRepositoryInterface
{
    public function findById(UserId $id): ?User;

    public function findByIdForUpdate(UserId $id): ?User;

    public function findPairForUpdate(UserId $payerId, UserId $payeeId): LockedUserPair;

    public function save(User $user): void;
}
