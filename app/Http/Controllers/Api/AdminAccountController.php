<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAdminRequest;
use App\Http\Requests\UpdateAdminRequest;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminAccountController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = User::query()
            ->withTrashed()
            ->whereIn('role', [
                User::ROLE_ADMIN,
                User::ROLE_SUPER_ADMIN,
            ])
            ->orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $search = trim((string) $request->query('search'));

            $query->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%"));
        }

        $admins = $query->paginate(
            $request->query('per_page', 15)
        );

        return ApiResponse::paginated(
            $admins,
            'Admin accounts retrieved successfully.'
        );
    }

    public function store(StoreAdminRequest $request): JsonResponse
    {
        $user = User::query()->create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'password' => $request->validated('password'),
            'role' => User::ROLE_ADMIN,
        ]);

        return ApiResponse::success('Admin created successfully.', [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'created_at' => $user->created_at?->toIso8601String(),
        ], 201);
    }

    public function update(
        UpdateAdminRequest $request,
        User $admin
    ): JsonResponse {
        $actor = $request->user();

        if ($actor && $actor->id === $admin->id) {
            return ApiResponse::error(
                'You cannot modify your own admin account.',
                403
            );
        }

        if ($admin->isSuperAdmin()) {
            return ApiResponse::error(
                'The super administrator account cannot be modified through the admin management API.',
                403
            );
        }

        $payload = $request->validated();

        $passwordChanged = array_key_exists('password', $payload);
        $roleChanged = array_key_exists('role', $payload)
            && $payload['role'] !== $admin->role;

        DB::transaction(function () use (
            $admin,
            $payload,
            $passwordChanged,
            $roleChanged
        ) {
            $admin->fill($payload);
            $admin->save();

            if ($passwordChanged || $roleChanged) {
                $this->revokeAdminTokens($admin);
            }
        });

        return ApiResponse::success('Admin updated successfully.', [
            'id' => $admin->id,
            'name' => $admin->name,
            'email' => $admin->email,
            'role' => $admin->role,
            'updated_at' => $admin->updated_at?->toIso8601String(),
        ]);
    }

    public function destroy(User $admin): JsonResponse
    {
        $actor = request()->user();

        if ($actor instanceof User && $actor->id === $admin->id) {
            return ApiResponse::error(
                'You cannot remove your own admin account.',
                403
            );
        }

        if ($admin->isSuperAdmin()) {
            return ApiResponse::error(
                'The super administrator cannot be deleted through the admin management API.',
                403
            );
        }

        DB::transaction(function () use ($admin) {
            $this->revokeAdminTokens($admin);
            $admin->delete();
        });

        return ApiResponse::success('Admin deleted successfully.');
    }

    private function revokeAdminTokens(User $admin): void
    {
        $admin->tokens()->delete();

        DB::table('admin_refresh_tokens')
            ->where('user_id', $admin->id)
            ->whereNull('revoked_at')
            ->update([
                'revoked_at' => now(),
                'updated_at' => now(),
            ]);
    }
}
