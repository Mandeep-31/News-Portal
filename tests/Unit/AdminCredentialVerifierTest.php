<?php

namespace Tests\Unit;

use App\Services\AdminCredentialVerifier;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminCredentialVerifierTest extends TestCase
{
    public function test_it_accepts_the_configured_admin_credentials(): void
    {
        config([
            'admin.email' => 'admin@example.test',
            'admin.password_hash' => Hash::make('correct-password'),
        ]);

        $this->assertTrue(app(AdminCredentialVerifier::class)->verify('admin@example.test', 'correct-password'));
    }

    public function test_it_rejects_invalid_or_unconfigured_admin_credentials(): void
    {
        config([
            'admin.email' => 'admin@example.test',
            'admin.password_hash' => Hash::make('correct-password'),
        ]);

        $verifier = app(AdminCredentialVerifier::class);

        $this->assertFalse($verifier->verify('other@example.test', 'correct-password'));
        $this->assertFalse($verifier->verify('admin@example.test', 'wrong-password'));

        config(['admin.email' => null, 'admin.password_hash' => null]);
        $this->assertFalse($verifier->verify('admin@example.test', 'correct-password'));
    }
}
