<?php

declare(strict_types=1);

namespace HyperfTest\Unit\Domain\User;

use App\Domain\User\DocumentNumber;
use App\Domain\User\DocumentType;
use App\Domain\User\Exception\InvalidDocumentNumberException;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class DocumentNumberTest extends TestCase
{
    public function testCanCreateValidCpf(): void
    {
        $cpf = DocumentNumber::cpf('111.444.777-35');

        $this->assertSame('11144477735', $cpf->getValue());
        $this->assertSame(DocumentType::CPF, $cpf->getType());
        $this->assertSame('111.444.777-35', $cpf->formatted());
        $this->assertSame('11144477735', (string) $cpf);
    }

    public function testCanCreateValidCnpj(): void
    {
        $cnpj = DocumentNumber::cnpj('45.723.174/0001-10');

        $this->assertSame('45723174000110', $cnpj->getValue());
        $this->assertSame(DocumentType::CNPJ, $cnpj->getType());
        $this->assertSame('45.723.174/0001-10', $cnpj->formatted());
        $this->assertSame('45723174000110', (string) $cnpj);
    }

    public function testCpfCreationFailsWhenEmpty(): void
    {
        $this->expectException(InvalidDocumentNumberException::class);

        DocumentNumber::cpf('   ');
    }

    public function testCnpjCreationFailsWhenEmpty(): void
    {
        $this->expectException(InvalidDocumentNumberException::class);

        DocumentNumber::cnpj('');
    }

    public function testCpfCreationFailsWhenInvalid(): void
    {
        $this->expectException(InvalidDocumentNumberException::class);

        DocumentNumber::cpf('123.456.789-00');
    }

    public function testCnpjCreationFailsWhenInvalid(): void
    {
        $this->expectException(InvalidDocumentNumberException::class);

        DocumentNumber::cnpj('11.111.111/1111-11');
    }

    public function testEqualsReturnsTrueForSameCpf(): void
    {
        $cpf1 = DocumentNumber::cpf('111.444.777-35');
        $cpf2 = DocumentNumber::cpf('11144477735');

        $this->assertTrue($cpf1->equals($cpf2));
    }

    public function testEqualsReturnsFalseForDifferentValues(): void
    {
        $cpf1 = DocumentNumber::cpf('111.444.777-35');
        $cpf2 = DocumentNumber::cpf('935.411.347-80');

        $this->assertFalse($cpf1->equals($cpf2));
    }

    public function testEqualsReturnsFalseForDifferentTypes(): void
    {
        $cpf = DocumentNumber::cpf('111.444.777-35');
        $cnpj = DocumentNumber::cnpj('45.723.174/0001-10');

        $this->assertFalse($cpf->equals($cnpj));
    }

    public function testFormattedOutputForCpf(): void
    {
        $cpf = DocumentNumber::cpf('11144477735');

        $this->assertSame('111.444.777-35', $cpf->formatted());
    }

    public function testFormattedOutputForCnpj(): void
    {
        $cnpj = DocumentNumber::cnpj('45723174000110');

        $this->assertSame('45.723.174/0001-10', $cnpj->formatted());
    }
}
