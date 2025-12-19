<?php

declare(strict_types=1);

namespace App\Domain\User;

enum DocumentType
{
    case CPF;
    case CNPJ;
}