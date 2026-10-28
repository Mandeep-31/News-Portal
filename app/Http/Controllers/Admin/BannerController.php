<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BannerController extends Controller
{
    public function index()
    {
        $banners = Banner::orderBy('sort_order')->latest('id')->paginate(20);

        if (request()->expectsJson()) {
            $banners->getCollection()->transform(fn (Banner $banner) => $banner->toPublicArray());
            return ApiResponse::paginated($banners, 'Banners retrieved successfully.');
        }

        return view('admin.banners.index', compact('banners'));
    }

    public function create()
    {
        if (request()->expectsJson()) {
            return ApiResponse::success('Submit a POST to /api/admin/banners to create a banner.');
        }

        return view('admin.banners.create', ['banner' => new Banner]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request, requiredImage: true);
        $data['image'] = $request->file('image')->store('banners', 'public');
        $data['is_active'] = $request->has('is_active') ? $request->boolean('is_active') : true;
        $data['sort_order'] = $data['sort_order'] ?? 0;
        $banner = Banner::create($data);

        if ($request->expectsJson()) {
            return ApiResponse::success('Banner created successfully.', $banner->toPublicArray(), 201);
        }

        return redirect()->route('admin.banners.index')->with('status', 'Banner created.');
    }

    public function edit(Banner $banner)
    {
        if (request()->expectsJson()) {
            return ApiResponse::success('Banner retrieved successfully.', $banner->toPublicArray());
        }

        return view('admin.banners.edit', compact('banner'));
    }

    public function update(Request $request, Banner $banner)
    {
        $data = $this->validated($request);
        if ($request->hasFile('image')) {
            $newImage = $request->file('image')->store('banners', 'public');
            Storage::disk('public')->delete($banner->image);
            $data['image'] = $newImage;
        }
        $data['is_active'] = $request->has('is_active')
            ? $request->boolean('is_active')
            : $banner->is_active;
        $data['sort_order'] = $data['sort_order'] ?? $banner->sort_order;
        $banner->update($data);

        if ($request->expectsJson()) {
            return ApiResponse::success('Banner updated successfully.', $banner->fresh()->toPublicArray());
        }

        return redirect()->route('admin.banners.index')->with('status', 'Banner updated.');
    }

    public function destroy(Banner $banner)
    {
        Storage::disk('public')->delete($banner->image);
        $banner->delete();

        if (request()->expectsJson()) {
            return ApiResponse::success('Banner deleted successfully.');
        }

        return redirect()->route('admin.banners.index')->with('status', 'Banner deleted.');
    }

    private function validated(Request $request, bool $requiredImage = false): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'caption' => ['nullable', 'string', 'max:500'],
            'link_url' => ['nullable', 'url', 'max:2048'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'is_active' => ['sometimes', 'boolean'],
            'image' => [$requiredImage ? 'required' : 'sometimes', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);
    }
}
