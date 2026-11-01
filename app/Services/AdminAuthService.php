<?php

namespace App\Services;

use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;

class AdminAuthService
{
    public function __construct(
        private readonly AdminCredentialVerifier $credentials
    ) {}

    public function issueApiToken(string $email, string $password): ?string
    {
        if (! $this->credentials->verify($email, $password)) {
            return null;
        }

        $admin = User::query()
            ->where('email', $email)
            ->first();

        if (! $admin instanceof User || ! $admin->isAdmin()) {
            return null;
        }

        $abilities = $admin->isSuperAdmin()
            ? ['admin', 'super-admin']
            : ['admin'];

        return $admin->createToken('admin-api', $abilities)->plainTextToken;
    }

    public function revokeApiToken(PersonalAccessToken $token): void
    {
        $token->delete();
    }
}