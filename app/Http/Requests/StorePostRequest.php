<?php

namespace App\Http\Requests;

class StorePostRequest extends PostRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required_without:body', 'string'],
            'body' => ['required_without:description', 'string'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'priority' => ['required', 'integer', 'between:0,10'],
        ]);
    }
}
