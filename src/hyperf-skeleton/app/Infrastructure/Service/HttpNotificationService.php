<?php

declare(strict_types=1);

namespace App\Infrastructure\Service;

use App\Domain\Service\NotificationServiceInterface;
use App\DTO\TransferNotificationData;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

final class HttpNotificationService implements NotificationServiceInterface
{
    public function __construct(
        private ClientInterface $client,
        private string $notificationUrl
    ) {}

    public function notify(TransferNotificationData $data): void
    {
        try {
            $this->client->request('POST', $this->notificationUrl, [
                'json' => [
                    'transfer_id' => $data->transferId,
                    'payer_id' => $data->payerId,
                    'payee_id' => $data->payeeId,
                    'amount' => $data->amount,
                    'status' => $data->status,
                ]
            ]);
        } catch (GuzzleException) {
            // Silenciosamente falha pois handler já loga o erro
        }
    }
}
