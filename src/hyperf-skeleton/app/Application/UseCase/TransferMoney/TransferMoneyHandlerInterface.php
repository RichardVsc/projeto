<?php

declare(strict_types=1);

namespace App\Application\UseCase\TransferMoney;

interface TransferMoneyHandlerInterface
{
    public function handle(TransferMoneyCommand $command): TransferMoneyResponse;
}
