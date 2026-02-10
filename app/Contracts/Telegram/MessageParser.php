<?php

declare(strict_types=1);

namespace App\Contracts\Telegram;

interface MessageParser
{
    /**
     * Parse raw message text into structured transaction data.
     *
     * @param string $message Raw message text
     * @return array Parsed data with keys: amount, type, description, date, category_id, brand_id
     * @throws \InvalidArgumentException If message cannot be parsed
     */
    public function parse(string $message): array;

    /**
     * Check if this parser can handle the message.
     */
    public function canParse(string $message): bool;
}
