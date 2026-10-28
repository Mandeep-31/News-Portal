<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Support\ApiResponse;
use App\Services\AdminCredentialVerifier;

class AuthController extends Controller
{
    public function __construct(private readonly AdminCredentialVerifier $adminCredentials)
    {
    }

    public function showLogin()
    {
        if (request()->expectsJson()) {
            return ApiResponse::error(
                'Send login credentials with POST to /control-room/login.',
                405,
                ['required' => ['email', 'password']],
            );
        }

        if (session('is_admin')) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! $this->adminCredentials->verify($credentials['email'], $credentials['password'])) {
            if ($request->expectsJson()) {
                return ApiResponse::error('Invalid email or password.', 401);
            }

            return back()
                ->withErrors([
                    'email' => 'Invalid admin credentials.',
                ])
                ->onlyInput('email');
        }

        $request->session()->regenerate();
        $request->session()->put('is_admin', true);

        if ($request->expectsJson()) {
            return ApiResponse::success('Login successful.');
        }

        return redirect()->route('admin.dashboard');
    }

    public function logout(Request $request)
    {
        $request->session()->forget('is_admin');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            return ApiResponse::success('Logged out.');
        }

        return redirect()->route('admin.login');
    }
}
