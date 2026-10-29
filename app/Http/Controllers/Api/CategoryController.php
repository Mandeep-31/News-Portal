<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CategoryNewsRequest;
use App\Services\CategoryService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    public function __construct(private readonly CategoryService $categories) {}

    public function index(): JsonResponse
    {
        return ApiResponse::success(
            'Categories retrieved successfully.',
            $this->categories->publicCategories(),
        );
    }

    public function show(string $categoryId): JsonResponse
    {
        $category = $this->categories->publicCategory((int) $categoryId);
        if ($category === null) {
            return ApiResponse::error('Category not found.', 404);
        }

        return ApiResponse::success('Category retrieved successfully.', $category);
    }

    public function news(CategoryNewsRequest $request, string $categoryId): JsonResponse
    {
        $news = $this->categories->publicNews(
            (int) $categoryId,
            (int) ($request->validated('per_page') ?? 12),
        );
        if ($news === null) {
            return ApiResponse::error('Category not found.', 404);
        }

        return ApiResponse::paginated($news, 'Category news retrieved successfully.');
    }
}
