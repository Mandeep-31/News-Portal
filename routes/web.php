<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PostController;
use Illuminate\Support\Facades\Route;
Route::get('/', function () {
    return response('App is running', 200)
        ->header('Content-Type', 'text/plain');
})
    ->name('home');
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
