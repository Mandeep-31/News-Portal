<?php
namespace App\Services;
use App\Models\Category;
use App\Models\Post;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class PostService
{
    public function __construct(
        private readonly UniqueSlugGenerator $slugGenerator
    ) {}

    public function paginateAdmin(int $perPage = 20): LengthAwarePaginator
    {
        return Post::with('category')
            ->latest('created_at')
            ->paginate($perPage);
    }
    public function editorCategories(): Collection
    {
        return Category::orderBy('name')->get();
    }

    public function newPost(): Post
    {
        return new Post;
    }

    public function loadForEdit(Post $post): Post
    {
        return $post->load('category');
    }

    public function create(array $data): Post
    {
        $images = $this->storeImages($data);

        $isPublished = (bool) ($data['is_published'] ?? false);

        $post = Post::create([
            'title' => $data['title'],
            'slug' => $this->slugGenerator->generate(
                new Post,
                $data['title'],
                'news'
            ),
            'excerpt' => $data['excerpt'] ?? null,
            'body' => $data['description'] ?? $data['body'],
            'caption' => $data['caption'] ?? null,
            'category_id' => $data['category_id'],
            'author' => filled($data['author'] ?? null)
                ? $data['author']
                : null,
            'added_by' => 'Admin',
            'priority' => $data['priority'],
            'is_featured' => (bool) ($data['is_featured'] ?? false),
            'is_published' => $isPublished,
            'published_at' => $isPublished ? now() : null,
            'images' => $images,
            'featured_image' => $images[0] ?? null,
        ]);

        return $post->load('category');
    }

    public function update(Post $post, array $data): Post
    {
        $currentImages = $post->images
            ?: array_filter([$post->featured_image]);

        $imagesToRemove = array_intersect(
            $data['remove_images'] ?? [],
            $currentImages
        );

        $remainingImages = array_values(
            array_diff($currentImages, $imagesToRemove)
        );

        $newImages = $this->storeImages($data);
        $images = array_merge($remainingImages, $newImages);

        $updates = [];

        if (array_key_exists('title', $data)) {
            $updates['title'] = $data['title'];
            $updates['slug'] = $this->slugGenerator->generate(
                $post,
                $data['title'],
                'news'
            );
        }

        if (array_key_exists('excerpt', $data)) {
            $updates['excerpt'] = $data['excerpt'];
        }

        if (
            array_key_exists('description', $data)
            || array_key_exists('body', $data)
        ) {
            $updates['body'] = $data['description'] ?? $data['body'];
        }

        foreach (['caption', 'category_id', 'priority'] as $field) {
            if (array_key_exists($field, $data)) {
                $updates[$field] = $data[$field];
            }
        }

        if (array_key_exists('author', $data)) {
            $updates['author'] = filled($data['author'])
                ? $data['author']
                : null;
        }

        if (array_key_exists('is_featured', $data)) {
            $updates['is_featured'] = (bool) $data['is_featured'];
        }

        if (array_key_exists('is_published', $data)) {
            $isPublished = (bool) $data['is_published'];

            $updates['is_published'] = $isPublished;

            $updates['published_at'] = $isPublished
                ? ($post->published_at ?? now())
                : null;
        }

        if ($imagesToRemove !== [] || $newImages !== []) {
            $updates['images'] = $images;
            $updates['featured_image'] = $images[0] ?? null;
        }

        $post->update($updates);

        if ($imagesToRemove !== []) {
            Storage::disk('public')->delete($imagesToRemove);
        }

        return $post->fresh('category');
    }

    public function delete(Post $post): void
    {
        $imagePaths = array_unique(array_filter(array_merge(
            $post->images ?? [],
            [$post->featured_image],
        )));

        Storage::disk('public')->delete($imagePaths);

        $post->delete();
    }

    private function storeImages(array $data): array
    {
        return collect($data['images'] ?? [])
            ->map(fn ($image) => $image->store('news', 'public'))
            ->all();
    }
}
