<?php

namespace App\Http\Requests;

class UpdatePostRequest extends PostRequest
{
    public function rules(): array
    {
        return parent::rules();
    }
}
