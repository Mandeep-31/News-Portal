<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Banner;
use App\Models\Category;
use App\Support\ApiResponse;

class DashboardController extends Controller
{
    public function index()
    {
        $counts = [
            'total' => Post::count(),
            'live' => Post::published()->notExpired()->count(),
            'scheduled' => Post::where('is_published', true)->where('published_at', '>', now())->count(),
            'categories' => Category::count(),
            'banners' => Banner::count(),
            'active_banners' => Banner::where('is_active', true)->count(),
        ];

        if (request()->expectsJson()) {
            return ApiResponse::success('Admin dashboard retrieved successfully.', $counts);
        }

        return view('admin.dashboard', compact('counts'));
    }
}
