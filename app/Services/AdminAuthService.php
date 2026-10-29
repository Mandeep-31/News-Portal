<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;

class AdminAuthService
{
    public function __construct(private readonly AdminCredentialVerifier $credentials) {}

    public function issueApiToken(string $email, string $password): ?string
    {
        if (! $this->credentials->verify($email, $password)) {
            return null;
        }

        $admin = User::firstOrCreate(
            ['email' => config('admin.email')],
            [
                'name' => 'News Administrator',
                'password' => Str::random(64),
            ],
        );

        return $admin->createToken('admin-api', ['admin'])->plainTextToken;
    }

    public function revokeApiToken(PersonalAccessToken $token): void
    {
        $token->delete();
    }
}
