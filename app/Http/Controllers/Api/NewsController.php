<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\NewsIndexRequest;
use App\Http\Requests\NewsSearchRequest;
use App\Services\PublicNewsService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class NewsController extends Controller
{
    public function __construct(private readonly PublicNewsService $news) {}

    public function index(NewsIndexRequest $request): JsonResponse
    {
        $news = $this->news->paginate($request->validated());

        return ApiResponse::paginated($news, 'News retrieved successfully.');
    }

    public function search(NewsSearchRequest $request): JsonResponse
    {
        $news = $this->news->paginate($request->validated());

        return ApiResponse::paginated($news, 'News retrieved successfully.');
    }

    public function show(string $slug): JsonResponse
    {
        $post = $this->news->findPublishedBySlug($slug);
        if ($post === null) {
            return ApiResponse::error('News not found.', 404);
        }

        return ApiResponse::success('News retrieved successfully.', $post->toPublicArray());
    }
}
