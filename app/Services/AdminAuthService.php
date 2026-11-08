<?php
namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;

class AdminAuthService
{
    // private const ACCESS_TOKEN_LIFETIME_HOURS = 24;
    private const ACCESS_TOKEN_LIFETIME_MINUTES = 2;

    private const REFRESH_TOKEN_LIFETIME_DAYS = 7;

    public function __construct(
        private readonly AdminCredentialVerifier $credentials
    ) {}

    public function issueApiToken(string $email, string $password): ?array
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

        return DB::transaction(
            fn () => $this->issueTokenPair($admin)
        );
    }

    private function issueTokenPair(User $admin): array
{
    $abilities = $admin->isSuperAdmin()
        ? ['admin', 'super-admin']
        : ['admin'];

    // $accessExpiresAt = now()->addHours(
    //     self::ACCESS_TOKEN_LIFETIME_HOURS    
    // );

        $accessExpiresAt = now()->addMinutes(
            self::ACCESS_TOKEN_LIFETIME_MINUTES
        );


    $refreshExpiresAt = now()->addDays(
        self::REFRESH_TOKEN_LIFETIME_DAYS
    );

    $accessToken = $admin->createToken(
        'admin-api',
        $abilities,
        $accessExpiresAt
    );

    $refreshToken = bin2hex(random_bytes(64));

    DB::table('admin_refresh_tokens')->insert([
        'user_id' => $admin->id,
        'access_token_id' => $accessToken->accessToken->id,
        'token_hash' => hash('sha256', $refreshToken),
        'expires_at' => $refreshExpiresAt,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return [
        'user' => $admin,
        'token' => $accessToken->plainTextToken,
        'refresh_token' => $refreshToken,
        // 'expires_in' => 86400,
        'expires_in' => 120,
    ];
}

    public function refreshApiToken(string $plainRefreshToken): ?array
    {
        return DB::transaction(function () use ($plainRefreshToken) {
            $storedToken = DB::table('admin_refresh_tokens')
                ->where('token_hash', hash('sha256', $plainRefreshToken))
                ->lockForUpdate()
                ->first();

            if (
                ! $storedToken
                || $storedToken->revoked_at !== null
                || now()->greaterThanOrEqualTo($storedToken->expires_at)
            ) {
                return null;
            }

            $admin = User::query()->find($storedToken->user_id);

            if (! $admin || ! $admin->isAdmin()) {
                DB::table('admin_refresh_tokens')
                    ->where('id', $storedToken->id)
                    ->update([
                        'revoked_at' => now(),
                        'updated_at' => now(),
                    ]);

                return null;
            }

            // Revoke the previous access token.
            if ($storedToken->access_token_id) {
                PersonalAccessToken::query()
                    ->whereKey($storedToken->access_token_id)
                    ->delete();
            }

            // Revoke the previous refresh token.
            DB::table('admin_refresh_tokens')
                ->where('id', $storedToken->id)
                ->update([
                    'revoked_at' => now(),
                    'updated_at' => now(),
                ]);

            // Issue a new token pair.
            return $this->issueTokenPair($admin);
        });
    }

    public function revokeApiToken(PersonalAccessToken $token): void
    {
        DB::transaction(function () use ($token) {
            DB::table('admin_refresh_tokens')
                ->where('access_token_id', $token->id)
                ->whereNull('revoked_at')
                ->update([
                    'revoked_at' => now(),
                    'updated_at' => now(),
                ]);

            $token->delete();
        });
    }
}
