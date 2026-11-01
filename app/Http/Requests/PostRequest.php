<?php

namespace App\Http\Requests;

use App\Models\Post;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class PostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $data = [];

        foreach (['is_published', 'is_featured'] as $field) {
            if ($this->has($field)) {
                $value = $this->input($field);

                if (is_string($value)) {
                    $normalized = strtolower(trim($value));

                    if (in_array($normalized, ['true', 'false'], true)) {
                        $data[$field] = $normalized === 'true';
                    }
                }
            }
        }

        if ($data !== []) {
            $this->merge($data);
        }

        if ($this->has('priority')) {
            $this->merge([
                'priority' => strtolower(
                    trim((string) $this->input('priority'))
                ),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'description' => ['sometimes', 'string'],
            'body' => ['sometimes', 'string'],
            'caption' => ['nullable', 'string', 'max:500'],

            'category_id' => [
                'sometimes',
                'integer',
                'exists:categories,id',
            ],

            'author' => ['nullable', 'string', 'max:120'],

            'priority' => [
                'sometimes',
                'string',
                'in:high,medium,low',
            ],

            'is_published' => ['sometimes', 'boolean'],
            'is_featured' => ['sometimes', 'boolean'],

            // Scheduling is not supported.
            'published_at' => ['prohibited'],
            'publish_now' => ['prohibited'],

            'images' => ['sometimes', 'array', 'max:3'],

            'images.*' => [
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],

            'remove_images' => ['sometimes', 'array'],
            'remove_images.*' => ['string'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny([
                    'images',
                    'remove_images',
                ])) {
                    return;
                }

                $this->validateImageCount($validator);
            },
        ];
    }

    private function validateImageCount(Validator $validator): void
    {
        $post = $this->route('post');

        $currentImages = $post instanceof Post
            ? ($post->images ?: array_filter([$post->featured_image]))
            : [];

        $imagesToRemove = array_intersect(
            (array) $this->input('remove_images', []),
            $currentImages
        );

        $remainingImages = array_diff(
            $currentImages,
            $imagesToRemove
        );

        $newImages = count($this->file('images', []));

        if (count($remainingImages) + $newImages > 3) {
            $validator->errors()->add(
                'images',
                'A news item can have at most three images. Remove an existing image before adding another.'
            );
        }
    }
}
