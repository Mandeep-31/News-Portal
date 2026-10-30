<?php
use App\Http\Controllers\Admin\BannerController as AdminBannerController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PostController;
use App\Http\Controllers\Api\AdminAuthController;
use App\Http\Controllers\Api\BannerController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\NewsController;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
Route::get('/news', [NewsController::class, 'index']);
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{categoryId}', [CategoryController::class, 'show'])
    ->whereNumber('categoryId');
Route::get('/category/{categoryId}/news', [CategoryController::class, 'news'])
    ->whereNumber('categoryId');
Route::get('/news/search', [NewsController::class, 'search']);
Route::get('/news/{slug}', [NewsController::class, 'show']);
Route::get('/banners', [BannerController::class, 'index']);
Route::post('/admin/login', [AdminAuthController::class, 'login'])
    ->middleware('throttle:5,1');
Route::middleware(['auth:sanctum', 'admin.token'])->prefix('admin')->group(function () {
    Route::post('/logout', [AdminAuthController::class, 'logout']);
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::apiResource('/posts', PostController::class)
        ->except(['show'])
        ->names([
            'index' => 'api.admin.posts.index',
            'store' => 'api.admin.posts.store',
            'update' => 'api.admin.posts.update',
            'destroy' => 'api.admin.posts.destroy',
        ]);
    Route::apiResource('/banners', AdminBannerController::class)
        ->except(['show'])
        ->names([
            'index' => 'api.admin.banners.index',
            'store' => 'api.admin.banners.store',
            'update' => 'api.admin.banners.update',
            'destroy' => 'api.admin.banners.destroy',
        ]);
    Route::apiResource('/categories', AdminCategoryController::class)
        ->except(['show'])
        ->names([
            'index' => 'api.admin.categories.index',
            'store' => 'api.admin.categories.store',
            'update' => 'api.admin.categories.update',
            'destroy' => 'api.admin.categories.destroy',
        ]);
});

Route::get('/user', function (Request $request) {
    return ApiResponse::success('User retrieved successfully.', $request->user()->only([
        'id', 'name', 'email', 'email_verified_at', 'created_at', 'updated_at',
    ]));
})->middleware('auth:sanctum');
