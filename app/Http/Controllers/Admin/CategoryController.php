<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use App\Services\CategoryService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    public function __construct(private readonly CategoryService $categories) {}

    public function index(): JsonResponse
    {
        $categories = $this->categories->paginateAdmin();

        return ApiResponse::paginated(
            $categories,
            'Categories retrieved successfully.'
        );
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = $this->categories->create($request->validated());

        return ApiResponse::success(
            'Category created successfully.',
            $category,
            201
        );
    }

    public function update(
        UpdateCategoryRequest $request,
        Category $category
    ): JsonResponse {
        $category = $this->categories->update(
            $category,
            $request->validated()
        );

        return ApiResponse::success(
            'Category updated successfully.',
            $category
        );
    }

    public function destroy(Category $category): JsonResponse
    {
        $this->categories->delete($category);

        return ApiResponse::success(
            'Category deleted successfully.'
        );
    }
}
