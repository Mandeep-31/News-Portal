<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Models\Post;
use App\Services\PostService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PostController extends Controller
{
    public function __construct(private readonly PostService $posts) {}

    public function index(): View|JsonResponse
    {
        $posts = $this->posts->paginateAdmin();
        if (request()->expectsJson()) {
            return ApiResponse::paginated($posts, 'News retrieved successfully.');
        }

        return view('admin.posts.index', compact('posts'));
    }

    public function create(): View|JsonResponse
    {
        if (request()->expectsJson()) {
            return ApiResponse::success('Submit a POST to /control-room/posts with an existing category_id to create news.');
        }

        return view('admin.posts.create', [
            'post' => $this->posts->newPost(),
            'categories' => $this->posts->editorCategories(),
        ]);
    }

    public function store(StorePostRequest $request): RedirectResponse|JsonResponse
    {
        $post = $this->posts->create($request->validated());
        if ($request->expectsJson()) {
            return ApiResponse::success('News created successfully.', $post, 201);
        }

        return redirect()->route('admin.posts.index')->with('status', 'News saved.');
    }

    public function edit(Post $post): View|JsonResponse
    {
        if (request()->expectsJson()) {
            return ApiResponse::success('News retrieved successfully.', $this->posts->loadForEdit($post));
        }

        return view('admin.posts.edit', [
            'post' => $post,
            'categories' => $this->posts->editorCategories(),
        ]);
    }

    public function update(UpdatePostRequest $request, Post $post): RedirectResponse|JsonResponse
    {
        $post = $this->posts->update($post, $request->validated());
        if ($request->expectsJson()) {
            return ApiResponse::success('News updated successfully.', $post);
        }

        return redirect()->route('admin.posts.index')->with('status', 'News updated.');
    }

    public function destroy(Post $post): RedirectResponse|JsonResponse
    {
        $this->posts->delete($post);
        if (request()->expectsJson()) {
            return ApiResponse::success('News deleted successfully.');
        }

        return redirect()->route('admin.posts.index')->with('status', 'News deleted.');
    }
}
