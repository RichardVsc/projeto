<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Application\Service\TransactionManagerInterface;
use Hyperf\DbConnection\Db;

final class TransactionManager implements TransactionManagerInterface
{
    public function transaction(callable $callback): mixed
    {
        return Db::transaction($callback);
    }
}
