<?php

namespace App\Http\Requests;

use App\Models\Post;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Validator;

class PostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'description' => ['sometimes', 'string'],
            'body' => ['sometimes', 'string'],
            'caption' => ['nullable', 'string', 'max:500'],
            'category_id' => ['sometimes', 'integer', 'exists:categories,id'],
            'author' => ['nullable', 'string', 'max:120'],
            'priority' => ['sometimes', 'integer', 'between:0,10'],
            'published_at' => ['nullable', 'date'],
            'publish_now' => ['sometimes', 'boolean'],
            'is_featured' => ['sometimes', 'boolean'],
            'images' => ['sometimes', 'array', 'max:3'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            // 'image' => ['sometimes', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'remove_images' => ['sometimes', 'array'],
            'remove_images.*' => ['string'],
        ];
    }

    /** @return array<int, \Closure(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->hasAny(['published_at', 'images', 'image', 'remove_images'])) {
                return;
            }

            $this->validateSchedule($validator);
            $this->validateImageCount($validator);
        }];
    }

    private function validateSchedule(Validator $validator): void
    {
        if ($this->boolean('publish_now') || ! $this->filled('published_at')) {
            return;
        }

        $scheduledAt = Carbon::parse($this->input('published_at'));
        if ($scheduledAt->isFuture() && $scheduledAt->greaterThan(now()->addDay())) {
            $validator->errors()->add('published_at', 'News can only be scheduled up to 24 hours ahead.');
        }
    }

    private function validateImageCount(Validator $validator): void
    {
        $post = $this->route('post');
        $currentImages = $post instanceof Post
            ? ($post->images ?: array_filter([$post->featured_image]))
            : [];
        $imagesToRemove = array_intersect((array) $this->input('remove_images', []), $currentImages);
        $remainingImages = array_diff($currentImages, $imagesToRemove);
        $newImages = count((array) $this->file('images', [])) + (int) $this->hasFile('image');

        if (count($remainingImages) + $newImages > 3) {
            $validator->errors()->add(
                'images',
                'A news item can have at most three images. Remove an existing image before adding another.',
            );
        }
    }
}
