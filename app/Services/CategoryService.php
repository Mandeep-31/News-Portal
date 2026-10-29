<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Post;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class CategoryService
{
    public function __construct(private readonly UniqueSlugGenerator $slugGenerator) {}

    public function paginateAdmin(int $perPage = 20): LengthAwarePaginator
    {
        return Category::withCount('posts')->orderBy('name')->paginate($perPage);
    }

    public function publicCategories(): Collection
    {
        return Category::query()
            ->select(['id', 'name', 'slug', 'created_at', 'updated_at'])
            ->withCount(['posts as posts_count' => fn ($query) => $query->published()])
            ->orderBy('name')
            ->get();
    }

    public function publicCategory(int $categoryId): ?Category
    {
        return Category::query()
            ->select(['id', 'name', 'slug', 'created_at', 'updated_at'])
            ->withCount(['posts as posts_count' => fn ($query) => $query->published()])
            ->find($categoryId);
    }

    public function publicNews(int $categoryId, int $perPage = 12): ?LengthAwarePaginator
    {
        $category = Category::query()->find($categoryId);
        if ($category === null) {
            return null;
        }

        $news = $category->posts()
            ->published()
            ->with('category')
            ->orderByDesc('priority')
            ->orderByDesc('published_at')
            ->paginate($perPage);

        $news->getCollection()->transform(fn (Post $post) => $post->toPublicArray());

        return $news;
    }

    public function newCategory(): Category
    {
        return new Category;
    }

    public function create(array $data): Category
    {
        return Category::create([
            'name' => $data['name'],
            'slug' => $this->slugGenerator->generate(new Category, $data['name'], 'category'),
        ]);
    }

    public function update(Category $category, array $data): Category
    {
        $category->update([
            'name' => $data['name'],
            'slug' => $this->slugGenerator->generate($category, $data['name'], 'category'),
        ]);

        return $category->fresh();
    }

    public function delete(Category $category): void
    {
        if ($category->posts()->exists()) {
            throw ValidationException::withMessages([
                'category' => 'This category still has news. Reassign or delete its news before removing it.',
            ]);
        }

        $category->delete();
    }
}
