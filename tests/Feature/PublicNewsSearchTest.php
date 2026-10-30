<?php

namespace Tests\Feature;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicNewsSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_anonymous_search_returns_published_news_in_priority_order_with_pagination(): void
    {
        $this->freezeTime();
        $category = Category::create(['name' => 'Science', 'slug' => 'science']);
        $highPriority = $this->createPost($category, 'moon-high', [
            'title' => 'Moon discovery',
            'priority' => 9,
        ]);
        $oldStory = $this->createPost($category, 'moon-old', [
            'title' => 'Old moon report',
            'priority' => 2,
            'published_at' => now()->subDays(30),
        ]);
        $this->createPost($category, 'moon-draft', [
            'title' => 'Moon draft',
            'is_published' => false,
        ]);
        $this->createPost($category, 'moon-scheduled', [
            'title' => 'Moon scheduled',
            'published_at' => now()->addHour(),
        ]);

        $firstPage = $this->getJson('/api/news/search?q=moon&per_page=1');

        $firstPage->assertOk()
            ->assertJsonStructure(['success', 'message', 'data', 'errors', 'meta'])
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.id', $highPriority->id)
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('meta.per_page', 1)
            ->assertJsonPath('errors', null);

        $this->getJson('/api/news/search?q=moon&per_page=1&page=2')
            ->assertOk()
            ->assertJsonPath('data.0.id', $oldStory->id)
            ->assertJsonPath('meta.current_page', 2)
            ->assertJsonPath('meta.total', 2);
    }

    public function test_public_news_endpoints_share_full_post_and_category_serialization(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('news/serialized-story.jpg', 'image');
        $category = Category::create(['name' => 'Science', 'slug' => 'science']);
        $story = $this->createPost($category, 'serialized-story', [
            'title' => 'Serialized science story',
            'excerpt' => 'Public excerpt',
            'caption' => 'Image caption',
            'featured_image' => 'news/serialized-story.jpg',
            'images' => ['news/serialized-story.jpg'],
            'author' => 'Reporter',
            'added_by' => 'Admin',
            'priority' => 8,
            'is_featured' => true,
        ]);

        $endpoints = [
            ['/api/news', 'data.0'],
            ['/api/news/search?q=serialized', 'data.0'],
            ["/api/category/{$category->id}/news", 'data.0'],
            ['/api/news/serialized-story', 'data'],
        ];

        foreach ($endpoints as [$endpoint, $dataPath]) {
            $response = $this->getJson($endpoint);
            $response->assertOk()
                ->assertJsonPath("{$dataPath}.id", $story->id);

            $news = $response->json($dataPath);
            foreach ([
                'id', 'title', 'slug', 'excerpt', 'description', 'body', 'caption',
                'category_id', 'category', 'category_slug', 'author', 'added_by',
                'priority', 'is_featured', 'is_published', 'published_at',
                'featured_image', 'image', 'images', 'created_at', 'updated_at',
            ] as $field) {
                $this->assertArrayHasKey($field, $news);
            }

            $this->assertSame($category->id, $news['category_id']);
            $this->assertSame($news['category_id'], $news['category']['id']);
            $this->assertSame('Science', $news['category']['name']);
            $this->assertSame('science', $news['category']['slug']);
            $this->assertArrayHasKey('created_at', $news['category']);
            $this->assertArrayHasKey('updated_at', $news['category']);
            $this->assertSame('science', $news['category_slug']);
            $this->assertSame($news['body'], $news['description']);
            $this->assertSame($news['featured_image'], $news['image']);
            $this->assertSame([$news['featured_image']], $news['images']);
            $this->assertTrue($news['is_published']);
            $this->assertTrue($news['is_featured']);
        }

        $this->getJson('/api/news')->assertJsonPath('meta.per_page', 10);
        $this->getJson("/api/category/{$category->id}/news")->assertJsonPath('meta.per_page', 10);
    }

    public function test_search_matches_category_names_without_exposing_hidden_news(): void
    {
        $category = Category::create(['name' => 'Space Research', 'slug' => 'space-research']);
        $story = $this->createPost($category, 'new-telescope', [
            'title' => 'A new telescope',
            'body' => 'A report about distant stars.',
        ]);
        $this->createPost($category, 'hidden-telescope', [
            'title' => 'Another telescope',
            'is_published' => false,
        ]);

        $this->getJson('/api/news/search?q=research')
            ->assertOk()
            ->assertJsonPath('data.0.id', $story->id)
            ->assertJsonPath('meta.total', 1);
    }

    public function test_general_news_category_filter_uses_numeric_category_ids(): void
    {
        $science = Category::create(['name' => 'Science', 'slug' => 'science']);
        $sports = Category::create(['name' => 'Sports', 'slug' => 'sports']);
        $scienceStory = $this->createPost($science, 'science-story');
        $this->createPost($sports, 'sports-story');

        $this->getJson("/api/news?category={$science->id}")
            ->assertOk()
            ->assertJsonPath('data.0.id', $scienceStory->id)
            ->assertJsonPath('meta.total', 1);

        $this->getJson('/api/news?category=science')
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['errors' => ['category']]);
    }

    public function test_search_requires_a_query_and_validates_pagination(): void
    {
        $this->getJson('/api/news/search')
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('data', null)
            ->assertJsonStructure(['errors' => ['q']]);

        $this->getJson('/api/news/search?q=space&per_page=51')
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('data', null)
            ->assertJsonStructure(['errors' => ['per_page']]);
    }

    public function test_public_banner_endpoint_returns_only_active_banners_in_sort_order(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('banners/late.jpg', 'banner');
        Storage::disk('public')->put('banners/early.jpg', 'banner');
        Storage::disk('public')->put('banners/hidden.jpg', 'banner');
        $late = Banner::create([
            'title' => 'Later banner',
            'image' => 'banners/late.jpg',
            'is_active' => true,
            'sort_order' => 10,
        ]);
        $early = Banner::create([
            'title' => 'First banner',
            'image' => 'banners/early.jpg',
            'is_active' => true,
            'sort_order' => 1,
            'caption' => 'Public caption',
            'link_url' => 'https://example.test',
        ]);
        Banner::create([
            'title' => 'Hidden banner',
            'image' => 'banners/hidden.jpg',
            'is_active' => false,
            'sort_order' => 0,
        ]);

        $this->getJson('/api/banners')
            ->assertOk()
            ->assertJsonStructure(['success', 'message', 'data', 'errors'])
            ->assertJsonPath('data.0.id', $early->id)
            ->assertJsonPath('data.0.caption', 'Public caption')
            ->assertJsonPath('data.0.sort_order', 1)
            ->assertJsonPath('data.1.id', $late->id)
            ->assertJsonCount(2, 'data');
    }

    public function test_public_news_routes_do_not_accept_create_requests(): void
    {
        $this->postJson('/api/news', ['title' => 'Unauthorized story'])
            ->assertMethodNotAllowed()
            ->assertJsonPath('success', false)
            ->assertJsonPath('data', null);

        $this->assertDatabaseCount('posts', 0);
    }

    private function createPost(Category $category, string $slug, array $attributes = []): Post
    {
        return Post::create(array_merge([
            'title' => 'Searchable news story',
            'slug' => $slug,
            'body' => 'Moon news description',
            'category_id' => $category->id,
            'priority' => 1,
            'is_published' => true,
            'published_at' => now(),
        ], $attributes));
    }
}
