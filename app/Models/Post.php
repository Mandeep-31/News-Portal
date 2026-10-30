<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;

class Post extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'body',
        'caption',
        'featured_image',
        'images',
        'category_id',
        'author',
        'added_by',
        'is_featured',
        'priority',
        'is_published',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
            'is_published' => 'boolean',
            'published_at' => 'datetime',
            'images' => 'array',
        ];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('is_published', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function toPublicArray(): array
    {
        $images = $this->images ?: array_filter([$this->featured_image]);
        /** @var FilesystemAdapter $publicDisk */
        $publicDisk = Storage::disk('public');
        $imageUrls = array_map(fn ($path) => $publicDisk->url($path), $images);
        $category = $this->category;

        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'excerpt' => $this->excerpt,
            'body' => $this->body,
            'caption' => $this->caption,
            'category_id' => $this->category_id,
            'category' => $category === null ? null : [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'created_at' => $category->created_at?->toJSON(),
                'updated_at' => $category->updated_at?->toJSON(),
            ],
            'category_slug' => $category?->slug,
            'author' => $this->author,
            'added_by' => $this->added_by,
            'priority' => $this->priority,
            'is_featured' => $this->is_featured,
            'is_published' => $this->is_published,
            'published_at' => $this->published_at?->toIso8601String(),
            'featured_image' => $imageUrls[0] ?? null,
            'images' => $imageUrls,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
