<?php

namespace App\Services\Telegram;

use App\Contracts\Telegram\MessageParser;
use App\Domains\Category\Models\Category;
use Illuminate\Support\Str;

/**
 * Simple parser for Telegram transaction messages.
 *
 * Expected formats:
 * - "expense 50 lunch at restaurant"
 * - "income 1000 salary"
 * - "-50 groceries" (defaults to expense)
 * - "+500 freelance" (defaults to income)
 */
class SimpleMessageParser implements MessageParser
{
    public function parse(string $message): array
    {
        $message = trim($message);

        // Try to detect type and amount
        $type = $this->detectType($message);
        $amount = $this->extractAmount($message);
        $brandName = $this->extractBrandName($message);
        $categoryType = $this->detectCategoryType($type);

        if (! $amount || $amount <= 0) {
            throw new \InvalidArgumentException('Could not extract valid amount from message');
        }

        return [
            'amount' => $amount,
            'brand_name' => $brandName,
            'category_type' => $categoryType,
            'raw_message' => $message,
        ];
    }

    public function canParse(string $message): bool
    {
        try {
            $this->parse($message);

            return true;
        } catch (\Exception) {
            return false;
        }
    }

    private function detectType(string $message): string
    {
        $message = strtolower($message);

        // Check for explicit type indicators
        if (str_starts_with($message, 'income') || str_starts_with($message, '+')) {
            return 'income';
        }

        if (str_starts_with($message, 'expense') || str_starts_with($message, '-')) {
            return 'expense';
        }

        // Default to expense
        return 'expense';
    }

    private function extractAmount(string $message): ?float
    {
        // Match numbers with optional decimal
        if (preg_match('/(?:^|[^\d])(\d+(?:\.\d{1,2})?)/', $message, $matches)) {
            return (float) $matches[1];
        }

        return null;
    }

    private function extractBrandName(string $message): string
    {
        // Remove type prefix
        $description = preg_replace('/^(income|expense)\s+/i', '', $message);

        // Remove +/- signs at the beginning
        $description = ltrim($description, '+ -');

        // Remove amount (first number found)
        $description = preg_replace('/\d+(?:\.\d{1,2})?/', '', $description, 1);

        return trim($description) ?: 'Telegram';
    }

    private function detectCategoryType(string $type): string
    {
        return $type === 'income' ? Category::INCOME : Category::EXPENSES;
    }
}
