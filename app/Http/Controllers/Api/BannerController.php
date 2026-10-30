<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Services\BannerService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
class BannerController extends Controller
{
    public function __construct(private readonly BannerService $banners) {}

    public function index(): JsonResponse
    {
        return ApiResponse::success('Banners retrieved successfully.', $this->banners->activeBanners());
    }
}
