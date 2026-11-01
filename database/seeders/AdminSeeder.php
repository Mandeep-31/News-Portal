<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = trim((string) config('admin.email'));
        $passwordHash = trim((string) config('admin.password_hash'));

        if ($email === '' || $passwordHash === '') {
            throw new RuntimeException('Admin credentials are not configured. Set ADMIN_EMAIL and ADMIN_PASSWORD_HASH.');
        }

        if (! Hash::isHashed($passwordHash)) {
            throw new RuntimeException('Admin password hash must be a valid bcrypt or argon2 hash.');
        }

        User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => 'News Administrator',
                'password' => $passwordHash,
                'role' => User::ROLE_ADMIN,
            ],
        );
    }
}
