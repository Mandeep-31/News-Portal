<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 80)->unique();
            $table->string('slug', 100)->unique();
            $table->timestamps();
        });

        Schema::table('posts', function (Blueprint $table): void {
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
        });

        $names = DB::table('posts')->whereNotNull('category')->distinct()->pluck('category');
        $usedSlugs = [];

        foreach ($names as $name) {
            if (trim($name) === '') {
                continue;
            }

            $base = Str::slug($name) ?: 'category';
            $slug = $base;
            for ($suffix = 2; in_array($slug, $usedSlugs, true); $suffix++) {
                $slug = $base.'-'.$suffix;
            }
            $usedSlugs[] = $slug;

            $categoryId = DB::table('categories')->insertGetId([
                'name' => $name,
                'slug' => $slug,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('posts')->where('category', $name)->update(['category_id' => $categoryId]);
        }

        Schema::table('posts', function (Blueprint $table): void {
            $table->dropColumn('category');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table): void {
            $table->string('category')->nullable();
        });

        foreach (DB::table('categories')->get() as $category) {
            DB::table('posts')->where('category_id', $category->id)->update(['category' => $category->name]);
        }

        Schema::table('posts', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('category_id');
        });

        Schema::dropIfExists('categories');
    }
};
