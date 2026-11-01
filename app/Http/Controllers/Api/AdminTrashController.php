<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminTrashController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $items = [
            'posts' => Post::onlyTrashed()->with('category')->latest('deleted_at')->paginate($request->query('per_page', 15)),
            'banners' => Banner::onlyTrashed()->latest('deleted_at')->paginate($request->query('per_page', 15)),
            'categories' => Category::onlyTrashed()->latest('deleted_at')->paginate($request->query('per_page', 15)),
            'admins' => User::onlyTrashed()->whereIn('role', [User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN])->latest('deleted_at')->paginate($request->query('per_page', 15)),
        ];

        return ApiResponse::success('Deleted records retrieved successfully.', $items);
    }

    public function restore(string $type, int $id): JsonResponse
    {
        $model = match ($type) {
            'posts' => Post::onlyTrashed()->find($id),
            'banners' => Banner::onlyTrashed()->find($id),
            'categories' => Category::onlyTrashed()->find($id),
            'admins' => User::onlyTrashed()->find($id),
            default => null,
        };

        if (! $model) {
            return ApiResponse::error('Deleted record not found.', 404);
        }

        if ($model instanceof User && $model->isSuperAdmin()) {
            return ApiResponse::error('The super administrator cannot be restored here.', 403);
        }

        $model->restore();

        return ApiResponse::success('Record restored successfully.', [
            'type' => $type,
            'id' => $model->id,
        ]);
    }
}
