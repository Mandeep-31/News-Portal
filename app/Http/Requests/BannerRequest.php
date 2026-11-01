<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BannerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'link_url' => ['sometimes', 'nullable', 'url', 'max:2048'],
            'sort_order' => ['sometimes', 'required', 'integer', 'between:1,8'],
            'is_active' => ['sometimes', 'boolean'],
            'image' => [
                'sometimes',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],
        ];
    }
}
