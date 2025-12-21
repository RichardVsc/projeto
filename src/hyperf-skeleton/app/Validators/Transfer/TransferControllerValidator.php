<?php

declare(strict_types=1);

namespace App\Validators\Transfer;

use App\Validators\Exception\ValidationException;
use Hyperf\Validation\Contract\ValidatorFactoryInterface;

final class TransferControllerValidator
{
    public function __construct(
        private ValidatorFactoryInterface $validator
    ) {
    }

    public function validate(
        ?string $payerId,
        ?string $payeeId,
        ?int $amount
    ): void {
        $data = [
            'payer_id' => $payerId,
            'payee_id' => $payeeId,
            'amount' => $amount,
        ];

        $validator = $this->validator->make($data, $this->rules(), $this->messages());

        if ($validator->fails()) {
            throw ValidationException::fromErrors($validator->errors()->all());
        }
    }

    private function rules(): array
    {
        return [
            'payer_id' => 'required|string|uuid',
            'payee_id' => 'required|string|uuid',
            'amount' => 'required|integer|min:1',
        ];
    }

    private function messages(): array
    {
        return [
            'required' => 'The :attribute field is required.',
            'string' => 'The :attribute must be a string.',
            'uuid' => 'The :attribute must be a valid UUID.',
            'integer' => 'The :attribute must be an integer.',
            'min' => 'The :attribute must be greater than zero.',
        ];
    }
}
