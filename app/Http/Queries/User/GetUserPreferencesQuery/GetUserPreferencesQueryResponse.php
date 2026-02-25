<?php

namespace App\Http\Queries\User\GetUserPreferencesQuery;

use App\Domains\User\Models\UserPreference;
use Illuminate\Http\JsonResponse;

class GetUserPreferencesQueryResponse
{
    public function __construct(
        private readonly UserPreference $preferences
    ) {}

    public function toResponse(): JsonResponse
    {
        return response()->json([
            'preferences' => [
                'uuid' => $this->preferences->uuid,
                'currency' => $this->preferences->currency,
                'date_format' => $this->preferences->date_format,
                'theme' => $this->preferences->theme,
                'language' => $this->preferences->language,
                'default_transaction_type' => $this->preferences->default_transaction_type,
                'email_notifications' => $this->preferences->email_notifications,
                'push_notifications' => $this->preferences->push_notifications,
            ]
        ]);
    }
}
