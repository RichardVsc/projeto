<?php

declare(strict_types=1);

namespace App\Application\Listener;

use App\Domain\Transfer\Event\TransferCompleted;
use App\DTO\TransferNotificationData;
use App\Job\SendTransferNotificationJob;
use Hyperf\AsyncQueue\Driver\DriverFactory;
use Hyperf\Context\ApplicationContext;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;
use Psr\Log\LoggerInterface;

#[Listener]
class SendTransferNotificationListener implements ListenerInterface
{
    public function __construct(
        private LoggerInterface $logger
    ) {}

    public function listen(): array
    {
        return [
            TransferCompleted::class,
        ];
    }

    public function process(object $event): void
    {
        if (!$event instanceof TransferCompleted) {
            return;
        }

        $data = TransferNotificationData::fromTransfer($event->getTransfer());
        $job = new SendTransferNotificationJob($data);

        $driver = ApplicationContext::getContainer()
            ->get(DriverFactory::class)
            ->get('default');

        $driver->push($job);

        $this->logger->info('Transfer notification job queued', [
            'transfer_id' => $data->transferId,
        ]);
    }
}
