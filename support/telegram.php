<?php

declare(strict_types=1);

if (! function_exists('telegram_bot')) {
    /**
     * Get Telegram Bot instance.
     */
    function telegram_bot(): \Telegram\Bot\Api
    {
        return \Telegram\Bot\Laravel\Facades\Telegram::getFacadeRoot();
    }
}

if (! function_exists('telegram_send_message')) {
    /**
     * Send message via Telegram.
     */
    function telegram_send_message(string $chatId, string $message, array $options = []): \Telegram\Bot\Objects\Message
    {
        return \Telegram\Bot\Laravel\Facades\Telegram::sendMessage(array_merge([
            'chat_id' => $chatId,
            'text' => $message,
            'parse_mode' => 'HTML',
        ], $options));
    }
}

if (! function_exists('telegram_parse_transaction')) {
    /**
     * Parse transaction message using the parser.
     */
    function telegram_parse_transaction(string $message): array
    {
        return app(\App\Contracts\Telegram\MessageParser::class)->parse($message);
    }
}
