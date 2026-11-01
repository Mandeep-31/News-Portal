<?php

namespace App\Services;

use App\Models\Banner;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class BannerService
{
    private const MAX_ACTIVE_BANNERS = 8;

    public function paginateAdmin(int $perPage = 20): LengthAwarePaginator
    {
        return Banner::query()
            ->orderBy('sort_order')
            ->latest('id')
            ->paginate($perPage);
    }

    public function activeBanners(): Collection
    {
        return Banner::query()
            ->where('is_active', true)
            ->inRandomOrder()
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
        $isActive = (bool) ($data['is_active'] ?? true);

        if ($isActive) {
            $this->ensureActiveLimit();
        }

        $imagePath = $data['image']->store('banners', 'public');

        unset($data['caption']);

        try {
            return Banner::create([
                'title' => $data['title'],
                'image' => $imagePath,
                'link_url' => $data['link_url'] ?? null,
                'is_active' => $isActive,
                'sort_order' => $data['sort_order'],
            ]);
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($imagePath);

            throw $exception;
        }
    }

    public function update(Banner $banner, array $data): Banner
    {
        $isActive = (bool) ($data['is_active'] ?? $banner->is_active);

        if ($isActive && ! $banner->is_active) {
            $this->ensureActiveLimit($banner->id);
        }

        unset($data['caption']);

        $oldImage = $banner->image;
        $newImage = null;

        if (isset($data['image'])) {
            $newImage = $data['image']->store('banners', 'public');
            $data['image'] = $newImage;
        }

        $data['is_active'] = $isActive;

        try {
            $banner->update($data);
        } catch (\Throwable $exception) {
            if ($newImage !== null) {
                Storage::disk('public')->delete($newImage);
            }

            throw $exception;
        }

        if ($newImage !== null) {
            Storage::disk('public')->delete($oldImage);
        }

        return $banner->fresh();
    }

    public function delete(Banner $banner): void
    {
        $banner->delete();
    }

    private function ensureActiveLimit(?int $exceptId = null): void
    {
        $query = Banner::query()
            ->where('is_active', true);

        if ($exceptId !== null) {
            $query->where('id', '!=', $exceptId);
        }

        if ($query->count() >= self::MAX_ACTIVE_BANNERS) {
            throw ValidationException::withMessages([
                'is_active' => [
                    'You can have a maximum of 8 active banners.',
                ],
            ]);
        }
    }
}
