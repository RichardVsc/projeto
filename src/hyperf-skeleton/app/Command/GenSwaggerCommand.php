<?php

declare(strict_types=1);

namespace App\Command;

use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;

#[Command]
class GenSwaggerCommand extends HyperfCommand
{
    public function __construct()
    {
        parent::__construct('gen:swagger');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('Generate swagger JSON file');
    }

    public function handle()
    {
        try {
            $openapi = \OpenApi\Generator::scan([
                BASE_PATH . '/app/Swagger',
                BASE_PATH . '/app/Controller',
            ]);

            file_put_contents(BASE_PATH . '/swagger-api.json', $openapi->toJson());
            $this->line('Swagger gerado com sucesso!', 'info');
        } catch (\Exception $e) {
            $this->line('Erro: ' . $e->getMessage(), 'error');
        }
    }
}