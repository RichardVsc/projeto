<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Transfer\Transfer;

interface TransferRepositoryInterface
{
    public function save(Transfer $transfer): void;
}