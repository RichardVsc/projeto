<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Transfer\Transfer;

interface NotificationServiceInterface
{
    public function notify(Transfer $transfer): void;
}