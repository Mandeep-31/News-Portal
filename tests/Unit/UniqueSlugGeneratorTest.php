<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Post;
use App\Services\UniqueSlugGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UniqueSlugGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_adds_a_suffix_when_a_slug_is_already_used(): void
    {
        Category::create(['name' => 'Local', 'slug' => 'local']);

        $slug = app(UniqueSlugGenerator::class)->generate(new Category, 'Local', 'category');

        $this->assertSame('local-2', $slug);
    }

    public function test_it_does_not_treat_the_current_models_slug_as_a_collision(): void
    {
        $category = Category::create(['name' => 'Local', 'slug' => 'local']);

        $slug = app(UniqueSlugGenerator::class)->generate($category, 'Local', 'category');

        $this->assertSame('local', $slug);
    }

    public function test_it_uses_the_caller_fallback_for_an_empty_slug(): void
    {
        $slug = app(UniqueSlugGenerator::class)->generate(new Post, '!!!', 'news');

        $this->assertSame('news', $slug);
    }
}
