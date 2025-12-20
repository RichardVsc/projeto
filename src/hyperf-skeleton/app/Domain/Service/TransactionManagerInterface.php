<?php

declare(strict_types=1);

namespace App\Application\Service;

interface TransactionManagerInterface
{
    /**
     * Execute operations inside a database transaction.
     * 
     * @param callable $callback Operations to execute
     * @return mixed Result of callback
     * @throws \Throwable If transaction fails
     */
    public function transaction(callable $callback): mixed;
}