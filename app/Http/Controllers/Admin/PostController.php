<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Models\Post;
use App\Services\PostService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class PostController extends Controller
{
    public function __construct(private readonly PostService $posts) {}

    public function index(): JsonResponse
    {
        $posts = $this->posts->paginateAdmin();

        return ApiResponse::paginated(
            $posts,
            'News retrieved successfully.'
        );
    }

    public function store(StorePostRequest $request): JsonResponse
    {
        $post = $this->posts->create($request->validated());

        return ApiResponse::success(
            'News created successfully.',
            $post,
            201
        );
    }

    public function update(
        UpdatePostRequest $request,
        Post $post
    ): JsonResponse {
        $post = $this->posts->update(
            $post,
            $request->validated()
        );

        return ApiResponse::success(
            'News updated successfully.',
            $post
        );
    }

    public function destroy(Post $post): JsonResponse
    {
        $this->posts->delete($post);

        return ApiResponse::success(
            'News deleted successfully.'
        );
    }
}
