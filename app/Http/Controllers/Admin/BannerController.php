<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBannerRequest;
use App\Http\Requests\UpdateBannerRequest;
use App\Models\Banner;
use App\Services\BannerService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class BannerController extends Controller
{
    public function __construct(private readonly BannerService $banners) {}

    public function index(): View|JsonResponse
    {
        $banners = $this->banners->paginateAdmin();
        if (request()->expectsJson()) {
            $banners->getCollection()->transform(fn (Banner $banner) => $banner->toPublicArray());

            return ApiResponse::paginated($banners, 'Banners retrieved successfully.');
        }

        return view('admin.banners.index', compact('banners'));
    }

    public function create(): View|JsonResponse
    {
        if (request()->expectsJson()) {
            return ApiResponse::success('Submit a POST to /api/admin/banners to create a banner.');
        }

        return view('admin.banners.create', ['banner' => $this->banners->newBanner()]);
    }

    public function store(StoreBannerRequest $request): RedirectResponse|JsonResponse
    {
        $banner = $this->banners->create($request->validated());
        if ($request->expectsJson()) {
            return ApiResponse::success('Banner created successfully.', $banner->toPublicArray(), 201);
        }

        return redirect()->route('admin.banners.index')->with('status', 'Banner created.');
    }

    public function edit(Banner $banner): View|JsonResponse
    {
        if (request()->expectsJson()) {
            return ApiResponse::success('Banner retrieved successfully.', $banner->toPublicArray());
        }

        return view('admin.banners.edit', compact('banner'));
    }

    public function update(UpdateBannerRequest $request, Banner $banner): RedirectResponse|JsonResponse
    {
        $banner = $this->banners->update($banner, $request->validated());
        if ($request->expectsJson()) {
            return ApiResponse::success('Banner updated successfully.', $banner->toPublicArray());
        }

        return redirect()->route('admin.banners.index')->with('status', 'Banner updated.');
    }

    public function destroy(Banner $banner): RedirectResponse|JsonResponse
    {
        $this->banners->delete($banner);
        if (request()->expectsJson()) {
            return ApiResponse::success('Banner deleted successfully.');
        }

        return redirect()->route('admin.banners.index')->with('status', 'Banner deleted.');
    }
}
