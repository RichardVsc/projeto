<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Model;

use Hyperf\DbConnection\Model\Model;

class TransferModel extends Model
{
    public bool $incrementing = false;

    protected ?string $table = 'transfers';

    protected string $keyType = 'string';

    protected array $fillable = [
        'id',
        'payer_id',
        'payee_id',
        'amount',
        'status',
        'failure_reason',
        'authorized_at',
        'completed_at',
        'failed_at',
    ];

    protected array $casts = [
        'amount' => 'integer',
        'created_at' => 'datetime',
        'authorized_at' => 'datetime',
        'completed_at' => 'datetime',
        'failed_at' => 'datetime',
    ];
}
