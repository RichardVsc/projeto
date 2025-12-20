<?php

declare(strict_types=1);

namespace App\Domain\User;

enum DocumentType
{
    case CPF;
    case CNPJ;

    public function getLength(): int
    {
        return match ($this) {
            self::CPF => 11,
            self::CNPJ => 14,
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::CPF => 'CPF',
            self::CNPJ => 'CNPJ',
        };
    }

    public function getMask(): string
    {
        return match ($this) {
            self::CPF => '###.###.###-##',
            self::CNPJ => '##.###.###/####-##',
        };
    }
}
