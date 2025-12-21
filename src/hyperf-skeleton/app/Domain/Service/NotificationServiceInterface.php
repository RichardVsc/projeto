<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\DTO\TransferNotificationData;

interface NotificationServiceInterface
{
    public function notify(TransferNotificationData $transfer): void;
}