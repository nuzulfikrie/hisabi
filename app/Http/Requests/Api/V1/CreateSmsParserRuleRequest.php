<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class CreateSmsParserRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'bank_name' => 'required|string|max:255',
            'pattern' => 'required|string',
            'is_active' => 'boolean',
        ];
    }
}
