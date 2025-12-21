<?php

declare(strict_types=1);

use App\Application\Service\TransactionManagerInterface;
use App\Domain\Repository\TransferRepositoryInterface;
use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\Service\AuthorizationServiceInterface;
use App\Domain\Service\NotificationServiceInterface;
use App\Infrastructure\Persistence\Repository\EloquentTransferRepository;
use App\Infrastructure\Persistence\Repository\EloquentUserRepository;
use App\Infrastructure\Persistence\TransactionManager;
use App\Infrastructure\Service\HttpAuthorizationService;
use App\Infrastructure\Service\HttpNotificationService;
use GuzzleHttp\Client;

/*
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
return [
    UserRepositoryInterface::class => EloquentUserRepository::class,
    TransferRepositoryInterface::class => EloquentTransferRepository::class,
    TransactionManagerInterface::class => TransactionManager::class,

    AuthorizationServiceInterface::class => function () {
        $client = new Client(['timeout' => 5]);
        $url = 'https://util.devi.tools/api/v2/authorize';

        return new HttpAuthorizationService($client, $url);
    },
    NotificationServiceInterface::class => function () {
        $client = new Client(['timeout' => 5]);
        $url = 'https://util.devi.tools/api/v1/notify';

        return new HttpNotificationService($client, $url);
    },
];
