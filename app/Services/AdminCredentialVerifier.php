<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminCredentialVerifier
{
    public function verify(string $email, string $password): bool
    {
        $admin = User::query()
            ->where('email', $email)
            ->first();

        if (! $admin || ! $admin->isAdmin()) {
            return false;
        }

        return Hash::check($password, $admin->password);
    }
}