<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PostController;
use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\CategoryController;
Route::get('/', [HomeController::class, 'index'])
    ->name('home');
Route::get('/news/{slug}', [HomeController::class, 'show'])
    ->name('news.show');
Route::prefix('control-room')->name('admin.')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])
        ->name('login');
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1')
        ->name('login.submit');
    Route::middleware('admin.auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])
            ->name('logout');
        Route::get('/dashboard', [DashboardController::class, 'index'])
            ->name('dashboard');
        Route::resource('/posts', PostController::class)
            ->except(['show']);
        Route::resource('/banners', BannerController::class)
            ->except(['show']);
        Route::resource('/categories', CategoryController::class)
            ->except(['show']);
    });
});
