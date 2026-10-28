<?php

namespace App\Services;

use Illuminate\Support\Facades\Hash;

class AdminCredentialVerifier
{
    public function verify(string $email, string $password): bool
    {
        $adminEmail = config('admin.email');
        $adminHash = config('admin.password_hash');

        if (! $adminEmail || ! $adminHash) {
            return false;
        }

        return hash_equals($adminEmail, $email) && Hash::check($password, $adminHash);
    }
}
