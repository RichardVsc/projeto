<?php

declare(strict_types=1);

namespace App\Job;

use App\Domain\Service\NotificationServiceInterface;
use App\DTO\TransferNotificationData;
use Hyperf\AsyncQueue\Job;
use Hyperf\Context\ApplicationContext;
use Psr\Log\LoggerInterface;
use Throwable;

class SendTransferNotificationJob extends Job
{
    public function __construct(
        private TransferNotificationData $data
    ) {
    }

    public function handle(): void
    {
        $container = ApplicationContext::getContainer();
        $notificationService = $container->get(NotificationServiceInterface::class);
        $logger = $container->get(LoggerInterface::class);

        try {
            $notificationService->notify($this->data);

            $logger->info('Transfer notification sent', [
                'transfer_id' => $this->data->transferId,
            ]);
        } catch (Throwable $e) {
            $logger->warning('Failed to send transfer notification', [
                'transfer_id' => $this->data->transferId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
