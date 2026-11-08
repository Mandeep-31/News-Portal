<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Http\Requests\AdminLoginRequest;
use App\Services\AdminCredentialVerifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
class AuthController extends Controller
{
    public function __construct(
        private readonly AdminCredentialVerifier $adminCredentials
    ) {}
    public function showLogin(): RedirectResponse
    {
        return redirect()->route('admin.login');
    }
    public function login(AdminLoginRequest $request): RedirectResponse
    {
        $credentials = $request->validated();

        if (! $this->adminCredentials->verify(
            $credentials['email'],
            $credentials['password']
        )) {
            return redirect()->route('admin.login')->withErrors([
                'email' => 'Invalid email or password.',
            ]);
        }
        $request->session()->regenerate();
        $request->session()->put('is_admin', true);
        return redirect()->route('admin.dashboard');
    }
    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget('is_admin');
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('admin.login');
    }
}