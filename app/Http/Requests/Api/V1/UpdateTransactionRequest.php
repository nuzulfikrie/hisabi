<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => 'required|numeric|min:0',
            'brand_id' => 'required|integer|exists:brands,id',
            'created_at' => 'required|date',
            'note' => 'nullable|string|max:1000',
            'tags' => 'nullable|array',
            'tags.*' => 'string|uuid|exists:tags,uuid',
            'type' => 'nullable|string|in:home,personal',
            'description' => 'nullable|string|max:255',
        ];
    }
}
