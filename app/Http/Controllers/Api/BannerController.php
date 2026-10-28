<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Support\ApiResponse;

class BannerController extends Controller
{
    public function index()
    {
        $banners = Banner::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Banner $banner) => $banner->toPublicArray())
            ->values();

        return ApiResponse::success('Banners retrieved successfully.', $banners);
    }
}
