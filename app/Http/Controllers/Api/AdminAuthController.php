<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\ApiResponse;
use App\Services\AdminCredentialVerifier;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminAuthController extends Controller
{
    public function __construct(private readonly AdminCredentialVerifier $adminCredentials)
    {
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $adminEmail = config('admin.email');

        if (! $this->adminCredentials->verify($credentials['email'], $credentials['password'])) {
            return ApiResponse::error('Invalid email or password.', 401);
        }

        // The environment-configured admin credential is the authority for
        // minting the admin-scoped token. A normal user's tokens never gain
        // this ability through their account or password.
        $admin = User::firstOrCreate(
            ['email' => $adminEmail],
            [
                'name' => 'News Administrator',
                'password' => Str::random(64),
            ],
        );

        $token = $admin->createToken('admin-api', ['admin']);

        return ApiResponse::success('Login successful.', [
            'token_type' => 'Bearer',
            'token' => $token->plainTextToken,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return ApiResponse::success('Logged out.');
    }
}
