<?php

declare(strict_types=1);

namespace App\Controller\Swagger;

use App\Libraries\Swagger\HtmlSwagger;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Contract\ResponseInterface as HttpResponse;
use Psr\Http\Message\ResponseInterface;

#[Controller]
class SwaggerController
{
    public function __construct(private HttpResponse $response)
    {
    }

    #[GetMapping(path: '/swagger')]
    public function index(): ResponseInterface
    {
        $jsonPath = BASE_PATH . '/swagger-api.json';
        $json = file_exists($jsonPath) ? file_get_contents($jsonPath) : '{}';

        return $this->response->html(HtmlSwagger::get($json));
    }
}
