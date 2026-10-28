<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use App\Services\UniqueSlugGenerator;

class CategoryController extends Controller
{
    public function __construct(private readonly UniqueSlugGenerator $slugGenerator)
    {
    }

    public function index()
    {
        $categories = Category::withCount('posts')->orderBy('name')->paginate(20);

        if (request()->expectsJson()) {
            return ApiResponse::paginated($categories, 'Categories retrieved successfully.');
        }

        return view('admin.categories.index', compact('categories'));
    }

    public function create()
    {
        if (request()->expectsJson()) {
            return ApiResponse::success('Submit a POST to /api/admin/categories to create a category.');
        }

        return view('admin.categories.create', ['category' => new Category]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80', 'unique:categories,name'],
        ]);

        if (Category::count() >= 6) {
            throw ValidationException::withMessages([
                'name' => 'You can create up to six categories.',
            ]);
        }

        $category = Category::create([
            'name' => $data['name'],
            'slug' => $this->slugGenerator->generate(new Category, $data['name'], 'category'),
        ]);

        if ($request->expectsJson()) {
            return ApiResponse::success('Category created successfully.', $category, 201);
        }

        return redirect()->route('admin.categories.index')->with('status', 'Category created.');
    }

    public function edit(Category $category)
    {
        if (request()->expectsJson()) {
            return ApiResponse::success('Category retrieved successfully.', $category);
        }

        return view('admin.categories.edit', compact('category'));
    }

    public function update(Request $request, Category $category)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80', Rule::unique('categories', 'name')->ignore($category->id)],
        ]);

        $category->update([
            'name' => $data['name'],
            'slug' => $this->slugGenerator->generate($category, $data['name'], 'category'),
        ]);

        if ($request->expectsJson()) {
            return ApiResponse::success('Category updated successfully.', $category->fresh());
        }

        return redirect()->route('admin.categories.index')->with('status', 'Category updated.');
    }

    public function destroy(Category $category)
    {
        if ($category->posts()->exists()) {
            throw ValidationException::withMessages([
                'category' => 'This category still has news. Reassign or delete its news before removing it.',
            ]);
        }

        $category->delete();

        if (request()->expectsJson()) {
            return ApiResponse::success('Category deleted successfully.');
        }

        return redirect()->route('admin.categories.index')->with('status', 'Category deleted.');
    }
}
