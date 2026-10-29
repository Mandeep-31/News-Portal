<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminContentManagementTest extends TestCase
{
    use RefreshDatabase;

    private string $adminEmail = 'admin@example.test';

    private string $adminPassword = 'correct-admin-password';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'admin.email' => $this->adminEmail,
            'admin.password_hash' => Hash::make($this->adminPassword),
        ]);
    }

    public function test_admin_can_create_update_and_delete_a_post_through_the_api(): void
    {
        $token = $this->adminToken();
        $category = Category::create(['name' => 'Local', 'slug' => 'local']);
        $created = $this->withToken($token)->postJson('/api/admin/posts', [
            'title' => 'Service-managed story',
            'body' => 'Initial story body',
            'category_id' => $category->id,
            'priority' => 3,
        ])->assertCreated()
            ->assertJsonPath('data.title', 'Service-managed story');
        $postId = $created->json('data.id');

        $this->withToken($token)->putJson("/api/admin/posts/{$postId}", [
            'title' => 'Updated story',
            'body' => 'Updated story body',
            'category_id' => $category->id,
            'priority' => 8,
        ])->assertOk()
            ->assertJsonPath('data.title', 'Updated story')
            ->assertJsonPath('data.priority', 8);

        $this->withToken($token)->deleteJson("/api/admin/posts/{$postId}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('posts', ['id' => $postId]);
    }

    public function test_post_creation_still_requires_its_core_fields(): void
    {
        $token = $this->adminToken();

        $this->withToken($token)->postJson('/api/admin/posts', [
            'title' => 'Incomplete story',
        ])->assertUnprocessable()
            ->assertJsonStructure(['errors' => ['body', 'category_id', 'priority']]);

        $this->assertDatabaseCount('posts', 0);
    }

    public function test_admin_can_partially_update_a_post_without_changing_omitted_fields(): void
    {
        $token = $this->adminToken();
        $category = Category::create(['name' => 'Local', 'slug' => 'local']);
        $publishedAt = now()->subDays(2)->startOfMinute();
        $post = Post::create([
            'title' => 'Existing story',
            'slug' => 'existing-story',
            'excerpt' => 'Existing excerpt',
            'body' => 'Existing body',
            'category_id' => $category->id,
            'author' => 'Existing author',
            'priority' => 7,
            'is_featured' => true,
            'is_published' => true,
            'published_at' => $publishedAt,
        ]);

        $this->withToken($token)->putJson("/api/admin/posts/{$post->id}", [
            'title' => 'Updated story title',
        ])->assertOk()
            ->assertJsonPath('data.title', 'Updated story title');

        $post->refresh();
        $this->assertSame('updated-story-title', $post->slug);
        $this->assertSame('Existing body', $post->body);
        $this->assertSame('Existing excerpt', $post->excerpt);
        $this->assertSame($category->id, $post->category_id);
        $this->assertSame(7, $post->priority);
        $this->assertTrue($post->is_featured);
        $this->assertTrue($post->is_published);
        $this->assertTrue($post->published_at->equalTo($publishedAt));
    }

    public function test_admin_can_publish_a_post_immediately_during_update(): void
    {
        $this->freezeTime();
        $token = $this->adminToken();
        $category = Category::create(['name' => 'Local', 'slug' => 'local']);
        $post = Post::create([
            'title' => 'Draft story',
            'slug' => 'draft-story',
            'body' => 'Story body',
            'category_id' => $category->id,
            'is_published' => false,
        ]);
        $expectedPublishedAt = now();

        $this->withToken($token)->putJson("/api/admin/posts/{$post->id}", [
            'publish_now' => true,
        ])->assertOk()
            ->assertJsonPath('data.is_published', true);

        $post->refresh();
        $this->assertTrue($post->is_published);
        $this->assertSame($expectedPublishedAt->timestamp, $post->published_at->timestamp);
    }

    public function test_admin_can_schedule_a_post_during_update(): void
    {
        $this->freezeTime();
        $token = $this->adminToken();
        $category = Category::create(['name' => 'Local', 'slug' => 'local']);
        $post = Post::create([
            'title' => 'Draft story',
            'slug' => 'draft-story',
            'body' => 'Story body',
            'category_id' => $category->id,
            'is_published' => false,
        ]);
        $scheduledAt = now()->addHours(6)->startOfMinute();

        $this->withToken($token)->putJson("/api/admin/posts/{$post->id}", [
            'published_at' => $scheduledAt->toIso8601String(),
        ])->assertOk()
            ->assertJsonPath('data.is_published', true);

        $post->refresh();
        $this->assertTrue($post->is_published);
        $this->assertSame($scheduledAt->timestamp, $post->published_at->timestamp);
    }

    public function test_post_update_validation_does_not_return_the_category_deletion_message(): void
    {
        $token = $this->adminToken();
        $category = Category::create(['name' => 'Local', 'slug' => 'local']);
        $post = Post::create([
            'title' => 'Existing story',
            'slug' => 'existing-story',
            'body' => 'Existing body',
            'category_id' => $category->id,
        ]);

        $this->withToken($token)->putJson("/api/admin/posts/{$post->id}", [
            'priority' => 11,
        ])->assertUnprocessable()
            ->assertJsonPath('message', 'The given data was invalid.')
            ->assertJsonStructure(['errors' => ['priority']]);

        $this->assertDatabaseHas('posts', ['id' => $post->id, 'priority' => 0]);
    }

    public function test_post_request_keeps_the_24_hour_schedule_limit(): void
    {
        $token = $this->adminToken();
        $category = Category::create(['name' => 'Local', 'slug' => 'local']);

        $this->withToken($token)->postJson('/api/admin/posts', [
            'title' => 'Far future story',
            'body' => 'Story body',
            'category_id' => $category->id,
            'priority' => 3,
            'published_at' => now()->addDays(2)->toIso8601String(),
        ])->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('errors.published_at.0', 'News can only be scheduled up to 24 hours ahead.');
    }

    public function test_post_request_keeps_the_three_image_limit(): void
    {
        Storage::fake('public');
        $token = $this->adminToken();
        $category = Category::create(['name' => 'Local', 'slug' => 'local']);
        $headers = ['Authorization' => "Bearer {$token}", 'Accept' => 'application/json'];

        $this->post('/api/admin/posts', [
            'title' => 'Four image story',
            'body' => 'Story body',
            'category_id' => $category->id,
            'priority' => 3,
            'images' => [
                $this->pngUpload('one.png'),
                $this->pngUpload('two.png'),
                $this->pngUpload('three.png'),
                $this->pngUpload('four.png'),
            ],
        ], $headers)->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['errors' => ['images']]);

        $this->assertDatabaseCount('posts', 0);
        Storage::disk('public')->assertDirectoryEmpty('news');
    }

    public function test_admin_can_update_and_delete_categories_with_existing_post_protection(): void
    {
        $token = $this->adminToken();
        $created = $this->withToken($token)->postJson('/api/admin/categories', [
            'name' => 'Local News',
        ])->assertCreated();
        $categoryId = $created->json('data.id');
        $category = Category::findOrFail($categoryId);

        $this->withToken($token)->putJson("/api/admin/categories/{$categoryId}", [
            'name' => 'Regional News',
        ])->assertOk()
            ->assertJsonPath('data.name', 'Regional News')
            ->assertJsonPath('data.slug', 'regional-news');

        $post = Post::create([
            'title' => 'Category linked story',
            'slug' => 'category-linked-story',
            'body' => 'Story body',
            'category_id' => $category->id,
        ]);

        $this->withToken($token)->deleteJson("/api/admin/categories/{$categoryId}")
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'This category still has news. Reassign or delete its news before removing it.')
            ->assertJsonStructure(['success', 'message', 'data', 'errors' => ['category']]);

        $post->delete();

        $this->withToken($token)->deleteJson("/api/admin/categories/{$categoryId}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('categories', ['id' => $categoryId]);
    }

    public function test_admin_can_upload_update_and_delete_a_banner_image(): void
    {
        Storage::fake('public');
        $token = $this->adminToken();
        $headers = ['Authorization' => "Bearer {$token}", 'Accept' => 'application/json'];
        $created = $this->post('/api/admin/banners', [
            'title' => 'First banner',
            'image' => $this->pngUpload('first.png'),
        ], $headers)->assertCreated();
        $bannerId = $created->json('data.id');
        $firstPath = Str::after(parse_url($created->json('data.image'), PHP_URL_PATH), '/storage/');

        Storage::disk('public')->assertExists($firstPath);

        $updated = $this->put("/api/admin/banners/{$bannerId}", [
            'title' => 'Updated banner',
            'image' => $this->pngUpload('updated.png'),
        ], $headers)->assertOk()
            ->assertJsonPath('data.title', 'Updated banner');
        $updatedPath = Str::after(parse_url($updated->json('data.image'), PHP_URL_PATH), '/storage/');

        Storage::disk('public')->assertMissing($firstPath);
        Storage::disk('public')->assertExists($updatedPath);

        $this->deleteJson("/api/admin/banners/{$bannerId}", [], $headers)
            ->assertOk()
            ->assertJsonPath('success', true);

        Storage::disk('public')->assertMissing($updatedPath);
        $this->assertDatabaseMissing('banners', ['id' => $bannerId]);
    }

    private function adminToken(): string
    {
        $response = $this->postJson('/api/admin/login', [
            'email' => $this->adminEmail,
            'password' => $this->adminPassword,
        ])->assertOk();

        return $response->json('data.token');
    }

    private function pngUpload(string $filename): UploadedFile
    {
        $png = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAIAAACQd1PeAAAADUlEQVR4nGP4z8AAAAMBAQDJ/pLvAAAAAElFTkSuQmCC',
            true,
        );

        return UploadedFile::fake()->createWithContent($filename, $png);
    }
}
