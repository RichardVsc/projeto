<?php

declare(strict_types=1);

namespace App\Infrastructure\Service;

use App\Domain\Service\AuthorizationServiceInterface;
use App\Domain\Transfer\Transfer;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

final class HttpAuthorizationService implements AuthorizationServiceInterface
{
    public function __construct(
        private ClientInterface $client,
        private string $authorizationUrl
    ) {
    }

    public function authorize(Transfer $transfer): bool
    {
        try {
            $response = $this->client->request('GET', $this->authorizationUrl);

            return $response->getStatusCode() === 200;
        } catch (GuzzleException) {
            return false;
        }
    }
}
