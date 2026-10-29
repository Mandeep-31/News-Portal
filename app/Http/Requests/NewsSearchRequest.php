<?php

namespace App\Http\Requests;

class NewsSearchRequest extends NewsIndexRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'q' => ['required', 'string', 'max:200'],
        ]);
    }
}
