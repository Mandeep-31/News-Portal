<?php
namespace App\Http\Requests;

class StoreBannerRequest extends BannerRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'title' => ['required', 'string', 'max:255'],
            'sort_order' => ['required', 'integer', 'between:1,8'],
            'image' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],
        ]
        );
    }
}
