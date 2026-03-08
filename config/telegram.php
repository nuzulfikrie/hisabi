<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Telegram Bot Token
    |--------------------------------------------------------------------------
    |
    | Your Telegram Bot API token. Get this from @BotFather on Telegram.
    |
    */
    'bot_token' => env('TELEGRAM_BOT_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | Webhook URL
    |--------------------------------------------------------------------------
    |
    | The URL where Telegram will send webhook updates.
    | Must be HTTPS and accessible from the internet.
    |
    */
    'webhook_url' => env('TELEGRAM_WEBHOOK_URL'),

    /*
    |--------------------------------------------------------------------------
    | Bot Name
    |--------------------------------------------------------------------------
    |
    | The name of your bot (for display purposes).
    |
    */
    'bot_name' => env('TELEGRAM_BOT_NAME', 'HisabiBot'),

    /*
    |--------------------------------------------------------------------------
    | Webhook Secret Token
    |--------------------------------------------------------------------------
    |
    | Secret token to validate webhook requests.
    | Leave empty to disable validation.
    |
    */
    'webhook_secret' => env('TELEGRAM_WEBHOOK_SECRET'),
];
