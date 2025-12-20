<?php

declare(strict_types=1);

namespace App\Infrastructure\Service;

use App\Domain\Service\NotificationServiceInterface;
use App\Domain\Transfer\Transfer;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

final class HttpNotificationService implements NotificationServiceInterface
{
    public function __construct(
        private ClientInterface $client,
        private string $notificationUrl
    ) {}

    public function notify(Transfer $transfer): void
    {
        try {
            $this->client->request('POST', $this->notificationUrl, [
                'json' => [
                    'transfer_id' => $transfer->getId()->getValue(),
                    'payer_id' => $transfer->getPayerId()->getValue(),
                    'payee_id' => $transfer->getPayeeId()->getValue(),
                    'amount' => $transfer->getAmount()->toInt(),
                    'status' => $transfer->getStatus()->value,
                ]
            ]);
        } catch (GuzzleException) {
            // Silenciosamente falha pois handler já loga o erro
        }
    }
}
