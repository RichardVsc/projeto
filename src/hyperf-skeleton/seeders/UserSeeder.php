<?php

declare(strict_types=1);

use Hyperf\Database\Seeders\Seeder;
use Hyperf\DbConnection\Db;

use function Hyperf\Support\now;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Db::table('users')->insert([
            [
                'id' => '550e8400-e29b-41d4-a716-446655440001',
                'type' => 'COMMON',
                'name' => 'João Silva',
                'document_number' => '27882002003',
                'document_type' => 'CPF',
                'email' => 'joao.silva@example.com',
                'password' => password_hash('senha123', PASSWORD_ARGON2ID),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => '550e8400-e29b-41d4-a716-446655440002',
                'type' => 'MERCHANT',
                'name' => 'Loja ABC LTDA',
                'document_number' => '00417460000110',
                'document_type' => 'CNPJ',
                'email' => 'contato@lojaabc.com',
                'password' => password_hash('senha123', PASSWORD_ARGON2ID),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => '550e8400-e29b-41d4-a716-446655440003',
                'type' => 'COMMON',
                'name' => 'Maria Santos',
                'document_number' => '26419203015',
                'document_type' => 'CPF',
                'email' => 'maria.santos@example.com',
                'password' => password_hash('senha123', PASSWORD_ARGON2ID),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => '550e8400-e29b-41d4-a716-446655440004',
                'type' => 'COMMON',
                'name' => 'Pedro Costa',
                'document_number' => '84475591066',
                'document_type' => 'CPF',
                'email' => 'pedro.costa@example.com',
                'password' => password_hash('senha123', PASSWORD_ARGON2ID),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
