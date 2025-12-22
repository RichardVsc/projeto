<?php

declare(strict_types=1);

namespace App\Swagger;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'API de Transferências',
    description: 'API para realizar transferências financeiras entre usuários'
)]
#[OA\Server(
    url: 'http://localhost:9502',
    description: 'Servidor local'
)]
class SwaggerInfo
{
}
