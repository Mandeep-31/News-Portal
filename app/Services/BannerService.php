<?php

namespace App\Services;

use App\Models\Banner;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class BannerService
{
    public function paginateAdmin(int $perPage = 20): LengthAwarePaginator
    {
        return Banner::orderBy('sort_order')->latest('id')->paginate($perPage);
    }

    public function activeBanners(): Collection
    {
        return Banner::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Banner $banner) => $banner->toPublicArray())
            ->values();
    }

    public function newBanner(): Banner
    {
        return new Banner;
    }

    public function create(array $data): Banner
    {
        $data['image'] = $data['image']->store('banners', 'public');
        $data['is_active'] = array_key_exists('is_active', $data) ? (bool) $data['is_active'] : true;
        $data['sort_order'] = $data['sort_order'] ?? 0;

        return Banner::create($data);
    }

    public function update(Banner $banner, array $data): Banner
    {
        $oldImage = $banner->image;
        if (isset($data['image'])) {
            $data['image'] = $data['image']->store('banners', 'public');
        }

        $data['is_active'] = array_key_exists('is_active', $data)
            ? (bool) $data['is_active']
            : $banner->is_active;
        $data['sort_order'] = $data['sort_order'] ?? $banner->sort_order;
        $banner->update($data);

        if (isset($data['image'])) {
            Storage::disk('public')->delete($oldImage);
        }

        return $banner->fresh();
    }

    public function delete(Banner $banner): void
    {
        Storage::disk('public')->delete($banner->image);
        $banner->delete();
    }
}
