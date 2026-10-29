<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicCategoriesTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_category_list_includes_all_categories_and_published_counts(): void
    {
        $visibleCategory = Category::create(['name' => 'Technology', 'slug' => 'technology']);
        $emptyCategory = Category::create(['name' => 'Travel', 'slug' => 'travel']);
        $oldNewsCategory = Category::create(['name' => 'History', 'slug' => 'history']);

        $this->createPost($visibleCategory, 'published-story');
        $this->createPost($emptyCategory, 'draft-story', [
            'is_published' => false,
        ]);
        $this->createPost($oldNewsCategory, 'old-story', [
            'published_at' => now()->subDays(8),
        ]);

        $response = $this->getJson('/api/categories');

        $response->assertOk()
            ->assertJsonStructure(['success', 'message', 'data', 'errors'])
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Categories retrieved successfully.')
            ->assertJsonStructure(['data' => [['id', 'name', 'slug', 'created_at', 'updated_at', 'posts_count']]])
            ->assertJsonPath('data.0.id', $oldNewsCategory->id)
            ->assertJsonPath('data.0.name', 'History')
            ->assertJsonPath('data.0.slug', 'history')
            ->assertJsonPath('data.0.posts_count', 1)
            ->assertJsonPath('data.1.id', $visibleCategory->id)
            ->assertJsonPath('data.2.id', $emptyCategory->id)
            ->assertJsonPath('data.2.posts_count', 0)
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('errors', null);

        $this->assertModelExists($emptyCategory);
    }

    public function test_public_category_detail_uses_numeric_id_and_returns_category_only(): void
    {
        $category = Category::create(['name' => 'Technology', 'slug' => 'technology']);
        $this->createPost($category, 'published-story');

        $this->getJson("/api/categories/{$category->id}")
            ->assertOk()
            ->assertJsonStructure(['success', 'message', 'data' => [
                'id', 'name', 'slug', 'created_at', 'updated_at', 'posts_count',
            ], 'errors'])
            ->assertJsonPath('data.id', $category->id)
            ->assertJsonPath('data.name', 'Technology')
            ->assertJsonPath('data.posts_count', 1)
            ->assertJsonMissingPath('data.posts')
            ->assertJsonMissingPath('data.password');
    }

    public function test_unknown_public_category_id_returns_json_404(): void
    {
        $this->getJson('/api/categories/999999')
            ->assertNotFound()
            ->assertJsonStructure(['success', 'message', 'data', 'errors'])
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Category not found.')
            ->assertJsonPath('data', null);
    }

    public function test_category_ids_must_be_numeric(): void
    {
        $this->getJson('/api/categories/technology')->assertNotFound();
    }

    public function test_obsolete_nested_category_news_route_is_not_registered(): void
    {
        $this->getJson('/api/news/categories/technology')
            ->assertNotFound()
            ->assertJsonStructure(['success', 'message', 'data', 'errors'])
            ->assertJsonPath('success', false);
    }

    public function test_public_category_news_only_contains_visible_stories_in_priority_order_and_paginates(): void
    {
        $category = Category::create(['name' => 'Technology', 'slug' => 'technology']);
        $otherCategory = Category::create(['name' => 'Sports', 'slug' => 'sports']);

        $topPriority = $this->createPost($category, 'top-priority', ['priority' => 9]);
        $secondPriority = $this->createPost($category, 'second-priority', ['priority' => 7]);
        $thirdPriority = $this->createPost($category, 'third-priority', ['priority' => 3]);
        $this->createPost($category, 'draft-story', [
            'is_published' => false,
            'published_at' => now(),
        ]);
        $this->createPost($category, 'scheduled-story', [
            'published_at' => now()->addHour(),
        ]);
        $oldStory = $this->createPost($category, 'old-story', [
            'priority' => 0,
            'published_at' => now()->subDays(8),
        ]);
        $this->createPost($otherCategory, 'other-category-story');

        $firstPage = $this->getJson("/api/category/{$category->id}/news?per_page=2");

        $firstPage->assertOk()
            ->assertJsonStructure(['success', 'message', 'data', 'errors', 'meta'])
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Category news retrieved successfully.')
            ->assertJsonPath('data.0.id', $topPriority->id)
            ->assertJsonPath('data.1.id', $secondPriority->id)
            ->assertJsonPath('meta.total', 4)
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('errors', null);

        $secondPage = $this->getJson("/api/category/{$category->id}/news?per_page=2&page=2");

        $secondPage->assertOk()
            ->assertJsonPath('data.0.id', $thirdPriority->id)
            ->assertJsonPath('data.1.id', $oldStory->id)
            ->assertJsonPath('meta.current_page', 2)
            ->assertJsonPath('meta.total', 4);
    }

    public function test_old_published_news_and_its_images_are_retained(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('news/old-story.jpg', 'fake-image');
        $category = Category::create(['name' => 'History', 'slug' => 'history']);
        $post = $this->createPost($category, 'old-story', [
            'featured_image' => 'news/old-story.jpg',
            'images' => ['news/old-story.jpg'],
            'published_at' => now()->subDays(30),
        ]);

        $this->getJson('/api/news/old-story')->assertOk()
            ->assertJsonPath('data.id', $post->id);
        $this->getJson('/api/news')->assertOk()
            ->assertJsonPath('data.0.id', $post->id);

        Artisan::call('news:prune-expired');

        $this->assertModelExists($post);
        Storage::disk('public')->assertExists('news/old-story.jpg');
    }

    public function test_valid_category_without_visible_news_returns_an_empty_paginated_result(): void
    {
        $category = Category::create(['name' => 'Travel', 'slug' => 'travel']);

        $this->getJson("/api/category/{$category->id}/news")
            ->assertOk()
            ->assertJsonStructure(['success', 'message', 'data', 'errors', 'meta'])
            ->assertJsonPath('data', [])
            ->assertJsonPath('meta.total', 0)
            ->assertJsonPath('errors', null);
    }

    public function test_missing_category_returns_a_consistent_json_404(): void
    {
        $this->getJson('/api/category/999999/news')
            ->assertNotFound()
            ->assertJsonStructure(['success', 'message', 'data', 'errors'])
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Category not found.')
            ->assertJsonPath('data', null)
            ->assertJsonPath('errors', null);
    }

    public function test_invalid_category_news_page_size_returns_a_consistent_json_422(): void
    {
        $category = Category::create(['name' => 'Technology', 'slug' => 'technology']);

        $this->getJson("/api/category/{$category->id}/news?per_page=51")
            ->assertUnprocessable()
            ->assertJsonStructure(['success', 'message', 'data', 'errors' => ['per_page']])
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'The given data was invalid.')
            ->assertJsonPath('data', null);
    }

    public function test_public_request_cannot_create_an_admin_category(): void
    {
        $this->postJson('/api/admin/categories', [
            'name' => 'Unauthorized',
        ])->assertUnauthorized()
            ->assertJsonStructure(['success', 'message', 'data', 'errors'])
            ->assertJsonPath('data', null);

        $this->assertDatabaseMissing('categories', ['slug' => 'unauthorized']);
    }

    private function createPost(Category $category, string $slug, array $attributes = []): Post
    {
        return Post::create(array_merge([
            'title' => str($slug)->replace('-', ' ')->title()->toString(),
            'slug' => $slug,
            'body' => 'News story body',
            'category_id' => $category->id,
            'priority' => 1,
            'is_published' => true,
            'published_at' => now(),
        ], $attributes));
    }
}
