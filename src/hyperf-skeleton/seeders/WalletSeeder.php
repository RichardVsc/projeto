<?php

declare(strict_types=1);

use Hyperf\Database\Seeders\Seeder;
use Hyperf\DbConnection\Db;
use Hyperf\Stringable\Str;

use function Hyperf\Support\now;

class WalletSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        Db::table('wallets')->insert([
            [
                'id' => Str::uuid()->toString(),
                'user_id' => '550e8400-e29b-41d4-a716-446655440001',
                'balance' => 100000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid()->toString(),
                'user_id' => '550e8400-e29b-41d4-a716-446655440002',
                'balance' => 50000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid()->toString(),
                'user_id' => '550e8400-e29b-41d4-a716-446655440003',
                'balance' => 20000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid()->toString(),
                'user_id' => '550e8400-e29b-41d4-a716-446655440004',
                'balance' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
