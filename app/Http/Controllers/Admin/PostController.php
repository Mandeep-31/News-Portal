<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Post;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;
use App\Services\UniqueSlugGenerator;

class PostController extends Controller
{
    public function __construct(private readonly UniqueSlugGenerator $slugGenerator)
    {
    }

    public function index()
    {
        $posts = Post::with('category')->latest('created_at')->paginate(20);
        if (request()->expectsJson()) {
            return ApiResponse::paginated($posts, 'News retrieved successfully.');
        }

        return view('admin.posts.index', compact('posts'));
    }

    public function create()
    {
        if (request()->expectsJson()) {
            return ApiResponse::success('Submit a POST to /control-room/posts with an existing category_id to create news.');
        }

        return view('admin.posts.create', ['post' => new Post, 'categories' => Category::orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['slug'] = $this->slugGenerator->generate(new Post, $data['title'], 'news');
        $data['author'] = filled($data['author'] ?? null) ? $data['author'] : null;
        $data['added_by'] = 'Admin';
        $data['images'] = $this->storeImages($request);
        $data['featured_image'] = $data['images'][0] ?? null;
        $data['is_published'] = $request->boolean('publish_now') || ! empty($data['published_at']);
        $data['published_at'] = $request->boolean('publish_now') ? now() : ($data['published_at'] ?? null);
        $data['is_featured'] = $request->boolean('is_featured');

        $post = Post::create($data);

        if ($request->expectsJson()) {
            return ApiResponse::success('News created successfully.', $post->load('category'), 201);
        }

        return redirect()->route('admin.posts.index')->with('status', 'News saved.');
    }

    public function edit(Post $post)
    {
        if (request()->expectsJson()) {
            return ApiResponse::success('News retrieved successfully.', $post->load('category'));
        }

        return view('admin.posts.edit', ['post' => $post, 'categories' => Category::orderBy('name')->get()]);
    }

    public function update(Request $request, Post $post)
    {
        $data = $this->validated($request);
        $data['author'] = filled($data['author'] ?? null) ? $data['author'] : null;
        $data['is_published'] = $request->boolean('publish_now') || ! empty($data['published_at']);
        $data['published_at'] = $request->boolean('publish_now') ? now() : ($data['published_at'] ?? null);
        $data['is_featured'] = $request->boolean('is_featured');

        $images = $post->images ?? array_filter([$post->featured_image]);
        $removedImages = [];
        foreach ($request->input('remove_images', []) as $path) {
            if (in_array($path, $images, true)) {
                $removedImages[] = $path;
                $images = array_values(array_diff($images, [$path]));
            }
        }
        if (count($images) + count($request->file('images', [])) + (int) $request->hasFile('image') > 3) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'images' => 'A news item can have at most three images. Remove an existing image before adding another.',
            ]);
        }
        $newImages = $this->storeImages($request);
        $data['images'] = array_merge($images, $newImages);
        $data['featured_image'] = $data['images'][0] ?? null;
        $post->update($data);
        Storage::disk('public')->delete($removedImages);

        if ($request->expectsJson()) {
            return ApiResponse::success('News updated successfully.', $post->fresh('category'));
        }

        return redirect()->route('admin.posts.index')->with('status', 'News updated.');
    }

    public function destroy(Post $post)
    {
        Storage::disk('public')->delete($post->images ?? array_filter([$post->featured_image]));
        $post->delete();
        if (request()->expectsJson()) {
            return ApiResponse::success('News deleted successfully.');
        }

        return redirect()->route('admin.posts.index')->with('status', 'News deleted.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'description' => ['required_without:body', 'string'],
            'body' => ['required_without:description', 'string'],
            'caption' => ['nullable', 'string', 'max:500'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'author' => ['nullable', 'string', 'max:120'],
            'priority' => ['required', 'integer', 'between:0,10'],
            'published_at' => ['nullable', 'date'],
            'images' => ['sometimes', 'array', 'max:3'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'image' => ['sometimes', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'remove_images' => ['sometimes', 'array'],
            'remove_images.*' => ['string'],
        ]);
        $data['body'] = $data['description'] ?? $data['body'];
        unset($data['description']);
        unset($data['image']);

        if (count($request->file('images', [])) + (int) $request->hasFile('image') > 3) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'images' => 'A news item can have at most three images.',
            ]);
        }

        if (! $request->boolean('publish_now') && ! empty($data['published_at'])) {
            $scheduled = Carbon::parse($data['published_at']);
            if ($scheduled->isFuture() && $scheduled->greaterThan(now()->addDay())) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'published_at' => 'News can only be scheduled up to 24 hours ahead.',
                ]);
            }
        }
        return $data;
    }

    private function storeImages(Request $request): array
    {
        $files = array_merge($request->file('images', []), array_filter([$request->file('image')]));

        return collect($files)
            ->map(fn ($image) => $image->store('news', 'public'))
            ->all();
    }

}
