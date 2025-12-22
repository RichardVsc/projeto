<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\User\User;
use App\Domain\User\UserId;

interface UserRepositoryInterface
{
    public function findById(UserId $id): ?User;

    public function findByIdForUpdate(UserId $id): ?User;

    public function findManyByIdsForUpdate(array $ids): array;

    public function save(User $user): void;
}
