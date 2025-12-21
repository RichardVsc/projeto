<?php

declare(strict_types=1);

namespace App\Domain\User;

use App\Domain\User\Exception\InvalidDocumentNumberException;

final class DocumentNumber
{
    private string $value;

    private DocumentType $type;

    private function __construct(string $value, DocumentType $type)
    {
        $this->value = $value;
        $this->type = $type;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public static function cpf(string $value): self
    {
        $trimmed = trim($value);

        if ($trimmed === '') {
            throw InvalidDocumentNumberException::empty();
        }

        $cleanValue = preg_replace('/\D/', '', $trimmed);

        if (! self::isValidCpf($cleanValue)) {
            throw InvalidDocumentNumberException::invalidCpf($value);
        }

        return new self($cleanValue, DocumentType::CPF);
    }

    public static function cnpj(string $value): self
    {
        $trimmed = trim($value);

        if ($trimmed === '') {
            throw InvalidDocumentNumberException::empty();
        }

        $cleanValue = preg_replace('/\D/', '', $trimmed);

        if (! self::isValidCnpj($cleanValue)) {
            throw InvalidDocumentNumberException::invalidCnpj($value);
        }

        return new self($cleanValue, DocumentType::CNPJ);
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getType(): DocumentType
    {
        return $this->type;
    }

    public function formatted(): string
    {
        return match ($this->type) {
            DocumentType::CPF => sprintf(
                '%s.%s.%s-%s',
                substr($this->value, 0, 3),
                substr($this->value, 3, 3),
                substr($this->value, 6, 3),
                substr($this->value, 9, 2)
            ),
            DocumentType::CNPJ => sprintf(
                '%s.%s.%s/%s-%s',
                substr($this->value, 0, 2),
                substr($this->value, 2, 3),
                substr($this->value, 5, 3),
                substr($this->value, 8, 4),
                substr($this->value, 12, 2)
            ),
        };
    }

    public function equals(DocumentNumber $other): bool
    {
        return $this->value === $other->value
            && $this->type === $other->type;
    }

    private static function isValidCpf(string $cpf): bool
    {
        if (strlen($cpf) !== 11 || preg_match('/(\d)\1{10}/', $cpf)) {
            return false;
        }

        for ($t = 9; $t < 11; ++$t) {
            $sum = 0;
            for ($i = 0; $i < $t; ++$i) {
                $sum += (int) $cpf[$i] * (($t + 1) - $i);
            }
            $digit = ((10 * $sum) % 11) % 10;
            if ((int) $cpf[$t] !== $digit) {
                return false;
            }
        }

        return true;
    }

    private static function isValidCnpj(string $cnpj): bool
    {
        if (strlen($cnpj) !== 14 || preg_match('/(\d)\1{13}/', $cnpj)) {
            return false;
        }

        $lengths = [12, 13];
        $weights = [
            [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2],
            [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2],
        ];

        foreach ($lengths as $index => $length) {
            $sum = 0;
            for ($i = 0; $i < $length; ++$i) {
                $sum += (int) $cnpj[$i] * $weights[$index][$i];
            }
            $digit = ($sum % 11) < 2 ? 0 : 11 - ($sum % 11);
            if ((int) $cnpj[$length] !== $digit) {
                return false;
            }
        }

        return true;
    }
}
