<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AdminAuthenticationTest extends TestCase
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

    public function test_admin_can_log_in_and_receive_a_bearer_token(): void
    {
        $response = $this->postJson('/api/admin/login', [
            'email' => $this->adminEmail,
            'password' => $this->adminPassword,
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Login successful.')
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonStructure(['success', 'message', 'data' => ['token'], 'errors']);

        $this->assertDatabaseHas('personal_access_tokens', [
            'name' => 'admin-api',
            'abilities' => '["admin"]',
        ]);
    }

    public function test_invalid_credentials_do_not_issue_a_token(): void
    {
        $this->postJson('/api/admin/login', [])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('errors.email.0', 'The email field is required.')
            ->assertJsonPath('errors.password.0', 'The password field is required.');

        $this->postJson('/api/admin/login', [
            'email' => $this->adminEmail,
            'password' => 'incorrect-password',
        ])->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Invalid email or password.')
            ->assertJsonPath('errors', null);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_admin_can_log_in_repeatedly_and_reach_protected_api_routes(): void
    {
        foreach (range(1, 2) as $attempt) {
            $login = $this->postJson('/api/admin/login', [
                'email' => $this->adminEmail,
                'password' => $this->adminPassword,
            ])->assertOk();

            $this->withToken($login->json('data.token'))
                ->getJson('/api/admin/dashboard')
                ->assertOk()
                ->assertJsonPath('success', true)
                ->assertJsonStructure(['data' => ['total', 'live', 'scheduled', 'categories']]);
        }
    }

    public function test_protected_admin_api_rejects_unauthenticated_requests(): void
    {
        $this->getJson('/api/admin/dashboard')
            ->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Unauthenticated.')
            ->assertJsonPath('errors', null);

        $this->postJson('/api/admin/logout')->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonPath('errors', null);
    }

    public function test_unauthenticated_request_cannot_toggle_post_status(): void
    {
        $category = Category::create(['name' => 'Local', 'slug' => 'local']);
        $post = Post::create([
            'title' => 'Private draft',
            'slug' => 'private-draft',
            'body' => 'Story body',
            'category_id' => $category->id,
            'is_featured' => false,
            'is_published' => false,
        ]);

        $this->patchJson("/api/admin/posts/{$post->id}", [
            'is_published' => true,
            'is_featured' => true,
        ])->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Unauthenticated.');

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'is_published' => false,
            'is_featured' => false,
        ]);
    }

    public function test_logout_revokes_the_current_token_and_admin_can_log_in_again(): void
    {
        $login = $this->postJson('/api/admin/login', [
            'email' => $this->adminEmail,
            'password' => $this->adminPassword,
        ])->assertOk();

        $this->withToken($login->json('data.token'))
            ->postJson('/api/admin/logout')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Logged out.');

        $this->assertDatabaseCount('personal_access_tokens', 0);

        // The test application persists between simulated HTTP requests; clear
        // its cached guards so this assertion observes the revoked DB token.
        app('auth')->forgetGuards();

        $this->withToken($login->json('data.token'))
            ->getJson('/api/admin/dashboard')
            ->assertUnauthorized();

        $nextLogin = $this->postJson('/api/admin/login', [
            'email' => $this->adminEmail,
            'password' => $this->adminPassword,
        ])->assertOk();

        $this->withToken($nextLogin->json('data.token'))
            ->getJson('/api/admin/dashboard')
            ->assertOk();
    }

    public function test_an_ordinary_users_token_cannot_access_admin_api(): void
    {
        $ordinaryUser = User::factory()->create(['email' => 'reader@example.test']);
        $token = $ordinaryUser->createToken('reader', ['news:read'])->plainTextToken;

        $this->withToken($token)->getJson('/api/admin/dashboard')
            ->assertForbidden()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Administrator token required.')
            ->assertJsonPath('errors', null);

        $this->postJson('/api/admin/login', [
            'email' => 'reader@example.test',
            'password' => 'reader-password',
        ])->assertUnauthorized();
    }

    public function test_admin_news_api_returns_a_created_resource_and_standard_validation_errors(): void
    {
        $login = $this->postJson('/api/admin/login', [
            'email' => $this->adminEmail,
            'password' => $this->adminPassword,
        ])->assertOk();
        $token = $login->json('data.token');
        $category = Category::create(['name' => 'Local', 'slug' => 'local']);

        $this->withToken($token)->postJson('/api/admin/posts', [
            'title' => 'Admin API story',
            'body' => 'Story body',
            'category_id' => $category->id,
            'author' => 'Admin',
            'priority' => 6,
        ])->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'News created successfully.')
            ->assertJsonPath('data.slug', 'admin-api-story')
            ->assertJsonPath('data.priority', 6)
            ->assertJsonPath('errors', null);

        $this->withToken($token)->postJson('/api/admin/posts', [])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'The given data was invalid.')
            ->assertJsonStructure(['errors' => ['title', 'body', 'category_id', 'priority']]);
    }

    public function test_admin_can_create_more_than_six_categories(): void
    {
        $login = $this->postJson('/api/admin/login', [
            'email' => $this->adminEmail,
            'password' => $this->adminPassword,
        ])->assertOk();
        $token = $login->json('data.token');

        foreach (range(1, 7) as $number) {
            $this->withToken($token)
                ->postJson('/api/admin/categories', ['name' => "Category {$number}"])
                ->assertCreated()
                ->assertJsonPath('success', true);
        }

        $this->assertDatabaseCount('categories', 7);
    }

    public function test_browser_session_login_logout_and_login_again_still_work(): void
    {
        $credentials = ['email' => $this->adminEmail, 'password' => $this->adminPassword];

        $this->post('/control-room/login', $credentials)
            ->assertRedirect(route('admin.dashboard'));
        $this->get('/control-room/dashboard')->assertOk();

        $this->post('/control-room/logout')->assertRedirect(route('admin.login'));
        $this->get('/control-room/dashboard')->assertRedirect(route('admin.login'));

        $this->post('/control-room/login', $credentials)
            ->assertRedirect(route('admin.dashboard'));
        $this->get('/control-room/dashboard')->assertOk();
    }

    public function test_browser_login_and_logout_remain_in_the_csrf_protected_web_middleware_group(): void
    {
        $routes = app('router')->getRoutes();
        $loginRoute = $routes->getByName('admin.login.submit');
        $logoutRoute = $routes->getByName('admin.logout');

        $this->assertContains('web', $loginRoute->gatherMiddleware());
        $this->assertContains('web', $logoutRoute->gatherMiddleware());
        $this->assertContains(
            PreventRequestForgery::class,
            app('router')->getMiddlewareGroups()['web'],
        );
    }

    public function test_postman_web_requests_get_json_by_default_and_can_opt_into_the_csrf_form(): void
    {
        $postman = ['User-Agent' => 'PostmanRuntime/7.40.0'];

        $this->get('/control-room/login', $postman)
            ->assertStatus(405)
            ->assertJsonPath('success', false)
            ->assertJsonPath('errors', ['required' => ['email', 'password']])
            ->assertDontSee('<!DOCTYPE html>');

        $this->get('/control-room/dashboard', $postman)
            ->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Unauthenticated.');

        $this->get('/control-room/login', $postman + ['X-Use-Web-Session' => 'true'])
            ->assertOk()
            ->assertSee('<form', false);
    }

    public function test_public_news_and_category_endpoints_return_consistent_json_without_authentication(): void
    {
        $category = Category::create(['name' => 'Technology', 'slug' => 'technology']);
        $story = Post::create([
            'title' => 'Example news',
            'slug' => 'example-news',
            'body' => 'Example story body',
            'category_id' => $category->id,
            'priority' => 8,
            'is_published' => true,
            'published_at' => now(),
        ]);

        $this->get('/api/news')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'News retrieved successfully.')
            ->assertJsonPath('data.0.id', $story->id)
            ->assertJsonPath('data.0.title', 'Example news')
            ->assertJsonPath('data.0.slug', 'example-news')
            ->assertJsonPath('data.0.category.name', 'Technology')
            ->assertJsonPath('data.0.priority', 8)
            ->assertJsonPath('current_page', 1)
            ->assertJsonPath('total', 1)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('errors', null);

        $this->get('/api/categories')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.id', $category->id)
            ->assertJsonPath('data.0.name', 'Technology')
            ->assertJsonPath('data.0.slug', 'technology');

        $this->get("/api/categories/{$category->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $category->id)
            ->assertJsonPath('data.posts_count', 1);

        $this->get("/api/category/{$category->id}/news")
            ->assertOk()
            ->assertJsonPath('data.0.id', $story->id);

        $this->get('/api/news/example-news')
            ->assertOk()
            ->assertJsonPath('data.slug', 'example-news');
    }

    public function test_api_validation_and_missing_news_use_the_standard_error_envelope(): void
    {
        $this->getJson('/api/news?per_page=0')
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'The given data was invalid.')
            ->assertJsonStructure(['errors' => ['per_page']]);

        $this->getJson('/api/news/not-a-real-story')
            ->assertNotFound()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'News not found.')
            ->assertJsonPath('errors', null);
    }

    public function test_api_server_errors_do_not_expose_exception_details(): void
    {
        Route::middleware('api')->get('/api/testing/server-error', function () {
            throw new \RuntimeException('Internal exception detail');
        });

        $this->get('/api/testing/server-error', ['Accept' => '*/*'])
            ->assertInternalServerError()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Server error.')
            ->assertJsonPath('errors', null)
            ->assertDontSee('Internal exception detail');

        Route::middleware('api')->get('/api/testing/csrf-error', function () {
            throw new TokenMismatchException('CSRF token mismatch.');
        });

        $this->get('/api/testing/csrf-error')
            ->assertStatus(419)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'CSRF token mismatch.')
            ->assertJsonPath('errors', null);

        $this->getJson('/api/does-not-exist')
            ->assertNotFound()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Endpoint not found.');
    }
}
