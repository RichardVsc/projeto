<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Model;

use Hyperf\DbConnection\Model\Model;

class UserModel extends Model
{
    protected ?string $table = 'users';
    
    protected string $keyType = 'string';
    
    public bool $incrementing = false;
    
    protected array $fillable = [
        'id',
        'type',
        'name',
        'document_number',
        'document_type',
        'email',
        'password',
    ];

    protected array $hidden = [
        'password'
    ];
    
    protected array $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    
    public function wallet()
    {
        return $this->hasOne(WalletModel::class, 'user_id', 'id');
    }
}