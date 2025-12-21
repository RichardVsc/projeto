<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Model;

use Hyperf\DbConnection\Model\Model;

class WalletModel extends Model
{
    public bool $incrementing = false;

    protected ?string $table = 'wallets';

    protected string $keyType = 'string';

    protected array $fillable = [
        'id',
        'user_id',
        'balance',
    ];

    protected array $casts = [
        'balance' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(UserModel::class, 'user_id', 'id');
    }
}
