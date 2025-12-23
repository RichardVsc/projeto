<?php

declare(strict_types=1);

namespace App\Validators\Transfer;

interface TransferControllerValidatorInterface
{
    public function validate(
        string $payerId,
        string $payeeId,
        int $amount
    ): void;
}
