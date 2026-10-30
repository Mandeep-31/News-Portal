<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class NewsIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:200'],
            'category' => ['nullable', 'integer', 'exists:categories,id'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }
}
