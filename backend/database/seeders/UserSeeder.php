<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Akun demo. Tidak memakai Factory/Faker karena image produksi dipasang tanpa paket dev,
 * dan aman dijalankan berulang (updateOrCreate).
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@kepl.test'],
            ['name' => 'Admin KEPL', 'password' => 'password'],
        );

        User::updateOrCreate(
            ['email' => 'budi@kepl.test'],
            ['name' => 'Budi', 'password' => 'password'],
        );
    }
}
