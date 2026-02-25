<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserPreferencesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'currency' => 'sometimes|string|size:3',
            'date_format' => 'sometimes|string|in:DD/MM/YYYY,MM/DD/YYYY,YYYY-MM-DD,DD-MM-YYYY',
            'theme' => 'sometimes|string|in:light,dark,system',
            'language' => 'sometimes|string|in:en,ms,zh,ja,ko',
            'default_transaction_type' => 'sometimes|string|in:income,expense',
            'email_notifications' => 'sometimes|boolean',
            'push_notifications' => 'sometimes|boolean',
        ];
    }
}
