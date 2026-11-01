<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class AdminSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_the_initial_admin_account_from_configured_credentials(): void
    {
        config([
            'admin.email' => 'admin@example.test',
            'admin.password_hash' => Hash::make('correct-password'),
        ]);

        $this->artisan('db:seed', ['--class' => AdminSeeder::class]);

        $this->assertDatabaseHas('users', [
            'email' => 'admin@example.test',
        ]);

        $admin = User::query()->where('email', 'admin@example.test')->firstOrFail();
        $this->assertTrue(Hash::check('correct-password', $admin->password));
        $this->assertNotSame('correct-password', $admin->password);
    }

    public function test_it_does_not_create_duplicate_admin_accounts_when_run_multiple_times(): void
    {
        config([
            'admin.email' => 'admin@example.test',
            'admin.password_hash' => Hash::make('correct-password'),
        ]);

        $this->artisan('db:seed', ['--class' => AdminSeeder::class]);
        $this->artisan('db:seed', ['--class' => AdminSeeder::class]);

        $this->assertDatabaseCount('users', 1);
    }

    public function test_it_fails_when_admin_configuration_is_missing(): void
    {
        config([
            'admin.email' => null,
            'admin.password_hash' => null,
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Admin credentials are not configured');

        $seeder = new AdminSeeder;
        $seeder->run();
    }

    public function test_it_rejects_plaintext_admin_passwords(): void
    {
        config([
            'admin.email' => 'admin@example.test',
            'admin.password_hash' => 'plain-password',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('valid bcrypt or argon2 hash');

        $seeder = new AdminSeeder;
        $seeder->run();
    }
}
