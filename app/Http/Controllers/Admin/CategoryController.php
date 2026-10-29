<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use App\Services\CategoryService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function __construct(private readonly CategoryService $categories) {}

    public function index(): View|JsonResponse
    {
        $categories = $this->categories->paginateAdmin();
        if (request()->expectsJson()) {
            return ApiResponse::paginated($categories, 'Categories retrieved successfully.');
        }

        return view('admin.categories.index', compact('categories'));
    }

    public function create(): View|JsonResponse
    {
        if (request()->expectsJson()) {
            return ApiResponse::success('Submit a POST to /api/admin/categories to create a category.');
        }

        return view('admin.categories.create', ['category' => $this->categories->newCategory()]);
    }

    public function store(StoreCategoryRequest $request): RedirectResponse|JsonResponse
    {
        $category = $this->categories->create($request->validated());
        if ($request->expectsJson()) {
            return ApiResponse::success('Category created successfully.', $category, 201);
        }

        return redirect()->route('admin.categories.index')->with('status', 'Category created.');
    }

    public function edit(Category $category): View|JsonResponse
    {
        if (request()->expectsJson()) {
            return ApiResponse::success('Category retrieved successfully.', $category);
        }

        return view('admin.categories.edit', compact('category'));
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse|JsonResponse
    {
        $category = $this->categories->update($category, $request->validated());
        if ($request->expectsJson()) {
            return ApiResponse::success('Category updated successfully.', $category);
        }

        return redirect()->route('admin.categories.index')->with('status', 'Category updated.');
    }

    public function destroy(Category $category): RedirectResponse|JsonResponse
    {
        $this->categories->delete($category);
        if (request()->expectsJson()) {
            return ApiResponse::success('Category deleted successfully.');
        }

        return redirect()->route('admin.categories.index')->with('status', 'Category deleted.');
    }
}
