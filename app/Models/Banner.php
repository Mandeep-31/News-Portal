<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;

class Banner extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'image',
        'link_url',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function toPublicArray(): array
    {
        /** @var FilesystemAdapter $storage */
        $storage = Storage::disk('public');

        return [
            'id' => $this->id,
            'title' => $this->title,
            'image' => $storage->url($this->image),
            'link_url' => $this->link_url,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
