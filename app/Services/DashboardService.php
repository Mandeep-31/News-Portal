<?php

namespace App\Services;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Post;

class DashboardService
{
    public function counts(): array
    {
        return [
            'total' => Post::count(),
            'live' => Post::published()->count(),
            'scheduled' => Post::where('is_published', true)->where('published_at', '>', now())->count(),
            'categories' => Category::count(),
            'banners' => Banner::count(),
            'active_banners' => Banner::where('is_active', true)->count(),
        ];
    }
}
