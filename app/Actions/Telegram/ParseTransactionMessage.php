<?php

namespace App\Actions\Telegram;

use App\Contracts\Telegram\MessageParser;
use Illuminate\Support\Facades\Log;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Parse incoming Telegram message and extract transaction data.
 */
class ParseTransactionMessage
{
    use AsAction;

    public function __construct(
        private MessageParser $parser
    ) {
    }

    /**
     * Handle the action.
     *
     * @return array Parsed transaction data
     */
    public function handle(string $message, string $chatId, string $messageId): array
    {
        try {
            $parsedData = $this->parser->parse($message);

            Log::info('Telegram message parsed', [
                'chat_id' => $chatId,
                'message_id' => $messageId,
                'parsed_data' => $parsedData,
            ]);

            return $parsedData;
        } catch (\Exception $e) {
            Log::error('Failed to parse Telegram message', [
                'chat_id' => $chatId,
                'message_id' => $messageId,
                'message' => $message,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Parse as a job (async processing).
     */
    public function asJob(string $message, string $chatId, string $messageId): void
    {
        $this->handle($message, $chatId, $messageId);
    }
}
