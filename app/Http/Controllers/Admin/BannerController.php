<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBannerRequest;
use App\Http\Requests\UpdateBannerRequest;
use App\Models\Banner;
use App\Services\BannerService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class BannerController extends Controller
{
    public function __construct(private readonly BannerService $banners) {}

    public function index(): JsonResponse
    {
        $banners = $this->banners->paginateAdmin();

        $banners->through(
            fn (Banner $banner) => $banner->toPublicArray()
        );

        return ApiResponse::paginated(
            $banners,
            'Banners retrieved successfully.'
        );
    }

    public function store(StoreBannerRequest $request): JsonResponse
    {
        $banner = $this->banners->create($request->validated());

        return ApiResponse::success(
            'Banner created successfully.',
            $banner->toPublicArray(),
            201
        );
    }

    public function update(
        UpdateBannerRequest $request,
        Banner $banner
    ): JsonResponse {
        $banner = $this->banners->update(
            $banner,
            $request->validated()
        );

        return ApiResponse::success(
            'Banner updated successfully.',
            $banner->toPublicArray()
        );
    }

    public function destroy(Banner $banner): JsonResponse
    {
        $this->banners->delete($banner);

        return ApiResponse::success(
            'Banner deleted successfully.'
        );
    }
}
