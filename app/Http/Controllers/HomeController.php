<?php
namespace App\Http\Controllers;
use App\Models\Post;
use App\Models\Banner;
use App\Support\ApiResponse;

class HomeController extends Controller
{
    public function index()
    {
        $banners = Banner::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Banner $banner) => $banner->toPublicArray());
        $featured = Post::published()->notExpired()->with('category')
            ->orderByDesc('priority')
            ->orderByDesc('published_at')
            ->where('is_featured', true)
            ->first();
        $posts = Post::published()->notExpired()->with('category')
            ->orderByDesc('priority')
            ->orderByDesc('published_at')
            ->paginate(12);
        if (request()->expectsJson()) {
            return ApiResponse::success('News retrieved successfully.', [
                'banners' => $banners,
                'featured' => $featured,
                'posts' => $posts,
            ]);
        }
        return view('home', compact('banners', 'featured', 'posts'));
    }
    public function show(string $slug)
    {
        $post = Post::published()->notExpired()->with('category')
            ->where('slug', $slug)
            ->firstOrFail();

        if (request()->expectsJson()) {
            return ApiResponse::success('News retrieved successfully.', $post);
        }

        return view('news', compact('post'));
    }
}
