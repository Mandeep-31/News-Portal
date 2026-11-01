<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $name = trim((string) config('admin.super_admin.name', ''));
        $email = trim((string) config('admin.super_admin.email', ''));
        $password = (string) config('admin.super_admin.password', '');

        if ($name === '' || $email === '' || $password === '') {
            throw new RuntimeException(
                'Super admin credentials are not configured.'
            );
        }

        $admin = User::withTrashed()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => $password,
                'role' => User::ROLE_SUPER_ADMIN,
            ]
        );

        if ($admin->trashed()) {
            $admin->restore();
        }
    }
}