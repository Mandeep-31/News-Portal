<?php

namespace App\Http\Requests;

class UpdatePostRequest extends PostRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'is_published' => ['sometimes', 'boolean'],
        ]);
    }
}
