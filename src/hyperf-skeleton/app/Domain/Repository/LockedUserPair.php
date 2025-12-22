<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\User\User;

final readonly class LockedUserPair
{
    public function __construct(
        public User $payer,
        public User $payee,
    ) {
    }
}
