<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Transfer\Transfer;

interface AuthorizationServiceInterface
{
    public function authorize(Transfer $transfer): bool;
}