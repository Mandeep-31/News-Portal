<?php

namespace App\Services;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Post;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PublicNewsService
{
    public function paginate(array $filters, int $defaultPerPage = 12): LengthAwarePaginator
    {
        $news = $this->filteredPublishedNews($filters)
            ->orderByDesc('priority')
            ->orderByDesc('published_at')
            ->paginate($filters['per_page'] ?? $defaultPerPage);

        $news->getCollection()->transform(fn (Post $post) => $post->toPublicArray());

        return $news;
    }

    public function findPublishedBySlug(string $slug): ?Post
    {
        return Post::published()->with('category')->where('slug', $slug)->first();
    }

    public function homepage(array $filters, int $perPage, bool $includeSections = true): array
    {
        $banners = Banner::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Banner $banner) => $banner->toPublicArray());

        $visibleCategories = Category::query()
            ->whereHas('posts', fn (Builder $query) => $query->published())
            ->orderBy('name')
            ->get();

        $search = trim((string) ($filters['q'] ?? ''));
        $categoryId = isset($filters['category']) ? (int) $filters['category'] : null;
        if ($categoryId !== null && ! in_array($categoryId, $visibleCategories->modelKeys(), true)) {
            $categoryId = null;
        }

        $filteredPosts = $this->filteredPublishedNews([
            'q' => $search,
            'category_id' => $categoryId,
        ]);

        $featured = (clone $filteredPosts)
            ->where('is_featured', true)
            ->orderByDesc('priority')
            ->orderByDesc('published_at')
            ->first();

        $posts = $filteredPosts
            ->orderByDesc('priority')
            ->orderByDesc('published_at')
            ->paginate($perPage)
            ->withQueryString();

        $categorySections = $includeSections
            ? Category::query()
                ->whereHas('posts', fn (Builder $query) => $query->published())
                ->with(['posts' => fn (HasMany $query) => $query->published()
                    ->with('category')
                    ->orderByDesc('priority')
                    ->orderByDesc('published_at')
                    ->limit(4)])
                ->orderBy('name')
                ->get()
            : collect();

        return compact(
            'banners',
            'featured',
            'posts',
            'visibleCategories',
            'categorySections',
            'search',
            'categoryId',
        );
    }

    private function filteredPublishedNews(array $filters): Builder
    {
        return Post::published()
            ->with('category')
            ->when($filters['q'] ?? null, fn (Builder $query, string $term) => $query->where(fn (Builder $searchQuery) => $searchQuery
                ->where('title', 'like', "%{$term}%")
                ->orWhere('excerpt', 'like', "%{$term}%")
                ->orWhere('body', 'like', "%{$term}%")
                ->orWhereHas('category', fn (Builder $categoryQuery) => $categoryQuery
                    ->where('name', 'like', "%{$term}%"))))
            ->when($filters['category'] ?? null, fn (Builder $query, string $category) => $query->whereHas('category', fn (Builder $categoryQuery) => $categoryQuery
                ->where('name', $category)->orWhere('slug', $category)))
            ->when($filters['category_id'] ?? null, fn (Builder $query, int $categoryId) => $query->where('category_id', $categoryId));
    }
}
