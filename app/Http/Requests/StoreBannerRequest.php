<?php

namespace App\Http\Requests;

class StoreBannerRequest extends BannerRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);
    }
}
