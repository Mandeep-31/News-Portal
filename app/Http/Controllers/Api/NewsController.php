<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class NewsController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:200'],
            'category' => ['nullable', 'string', 'max:80'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $news = Post::published()->notExpired()->with('category')
            ->when($filters['q'] ?? null, fn ($query, $term) => $query->where(fn ($q) => $q
                ->where('title', 'like', "%{$term}%")
                ->orWhere('excerpt', 'like', "%{$term}%")
                ->orWhere('body', 'like', "%{$term}%")))
            ->when($filters['category'] ?? null, fn ($query, $category) => $query->whereHas('category', fn ($q) => $q
                ->where('name', $category)->orWhere('slug', $category)))
            ->when($filters['category_id'] ?? null, fn ($query, $categoryId) => $query->where('category_id', $categoryId))
            ->orderByDesc('priority')->orderByDesc('published_at')
            ->paginate($filters['per_page'] ?? 12);

        $news->getCollection()->transform(fn (Post $post) => $this->serialize($post));

        return ApiResponse::paginated($news, 'News retrieved successfully.');
    }

    public function show(string $slug)
    {
        $post = Post::published()->notExpired()->with('category')->where('slug', $slug)->firstOrFail();
        return ApiResponse::success('News retrieved successfully.', $this->serialize($post));
    }

    private function serialize(Post $post): array
    {
        $images = $post->images ?? array_filter([$post->featured_image]);
        return [
            'id' => $post->id,
            'title' => $post->title,
            'slug' => $post->slug,
            'excerpt' => $post->excerpt,
            'description' => $post->body,
            'body' => $post->body,
            'caption' => $post->caption,
            'category_id' => $post->category_id,
            'category' => $post->category?->name,
            'category_slug' => $post->category?->slug,
            'author' => $post->author,
            'added_by' => $post->added_by,
            'priority' => $post->priority,
            'is_featured' => $post->is_featured,
            'published_at' => $post->published_at?->toIso8601String(),
            'image' => isset($images[0]) ? Storage::disk('public')->url($images[0]) : null,
            'images' => array_map(fn ($path) => Storage::disk('public')->url($path), $images),
        ];
    }
}
