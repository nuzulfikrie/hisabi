<?php

declare(strict_types=1);

namespace App\Services\Ocr;

/**
 * Parse receipt text into structured transaction data
 * Supports Malaysian receipt formats: grocery, restaurant, fuel, utility
 */
class ReceiptParser
{
    /**
     * Common Malaysian merchant patterns and keywords
     */
    private const MERCHANT_KEYWORDS = [
        'LOTUS', 'MYDIN', 'GIANT', 'TESCO', 'AEON', 'G-MART', 'KK MART', '99 SPEEDMART',
        '7-ELEVEN', 'FAMILY MART', 'CU', 'SHELL', 'PETRONAS', 'PETRON', 'CALTEX', 'MOBIL',
        'MCDONALD', 'KFC', 'SUBWAY', 'STARBUCKS', 'COFFEE BEAN', 'OLDTOWN', 'PAPPARICH',
        'TNB', 'SYABAS', 'AIR SELANGOR', 'TM', 'MAXIS', 'DIGI', 'CELCOM', 'UMOBILE',
        'POS LAJU', 'J&T', 'DHL', 'NINJA VAN', 'CITY-LINK',
    ];

    /**
     * Amount-related keywords in English and Malay
     */
    private const AMOUNT_KEYWORDS = [
        'TOTAL', 'JUMLAH', 'GRAND TOTAL', 'AMOUNT DUE', 'BALANCE DUE',
        'SUBTOTAL', 'NET TOTAL', 'TO PAY', 'KENA BAYAR', 'JUMLAH BESAR',
    ];

    /**
     * Lines to skip when detecting merchant name
     */
    private const SKIP_MERCHANT_LINES = [
        'TAX INVOICE', 'INVOICE', 'RECEIPT', 'RESIT', 'CASH RECEIPT',
        'SIMPLIFIED TAX INVOICE', 'GST ID', 'SST ID', 'BRN', 'REG NO',
        'TEL:', 'FAX:', 'EMAIL:', 'WWW.', 'HTTP', 'DATE', 'TIME',
        'THANK YOU', 'TERIMA KASIH', 'COME AGAIN', 'SELAMAT DATANG',
    ];

    /**
     * Parse receipt text into structured data
     *
     * @return array<string, mixed>
     */
    public function parse(string $text): array
    {
        $lines = $this->extractLines($text);

        return [
            'merchant' => $this->extractMerchant($lines),
            'amount' => $this->extractAmount($text, $lines),
            'date' => $this->extractDate($text),
            'items' => $this->extractItems($lines),
            'raw_text' => $text,
        ];
    }

    /**
     * Extract clean lines from text
     *
     * @return array<string>
     */
    private function extractLines(string $text): array
    {
        $lines = explode("\n", $text);
        $cleanLines = [];

        foreach ($lines as $line) {
            $line = trim($line);
            // Remove excessive whitespace
            $line = preg_replace('/\s+/', ' ', $line);
            if ($line !== '' && strlen($line) > 1) {
                $cleanLines[] = $line;
            }
        }

        return $cleanLines;
    }

    /**
     * Extract merchant name from receipt lines
     *
     * @param array<string> $lines
     */
    private function extractMerchant(array $lines): ?string
    {
        // First, check for known merchant keywords
        foreach ($lines as $line) {
            $upperLine = strtoupper($line);
            foreach (self::MERCHANT_KEYWORDS as $keyword) {
                if (str_contains($upperLine, $keyword)) {
                    return $this->cleanMerchantName($line);
                }
            }
        }

        // Look at first few lines for business name
        $candidateLines = array_slice($lines, 0, 5);

        foreach ($candidateLines as $line) {
            // Skip lines that contain common non-merchant text
            if ($this->shouldSkipMerchantLine($line)) {
                continue;
            }

            // Look for lines that look like business names
            // - Not purely numeric
            // - Not dates
            // - Reasonable length (3-50 chars)
            // - Contains letters
            if ($this->isValidMerchantName($line)) {
                return $this->cleanMerchantName($line);
            }
        }

        return null;
    }

    /**
     * Check if a line should be skipped for merchant detection
     */
    private function shouldSkipMerchantLine(string $line): bool
    {
        $upperLine = strtoupper($line);

        foreach (self::SKIP_MERCHANT_LINES as $skip) {
            if (str_contains($upperLine, $skip)) {
                return true;
            }
        }

        // Skip if it's just a date or time
        if (preg_match('/^\d{1,2}[\/\-\.]\d{1,2}[\/\-\.]\d{2,4}$/', $line)) {
            return true;
        }

        if (preg_match('/^\d{1,2}:\d{2}/', $line)) {
            return true;
        }

        return false;
    }

    /**
     * Check if line looks like a valid merchant name
     */
    private function isValidMerchantName(string $line): bool
    {
        // Must contain letters
        if (! preg_match('/[a-zA-Z]/', $line)) {
            return false;
        }

        // Length check
        $length = strlen($line);
        if ($length < 3 || $length > 50) {
            return false;
        }

        // Shouldn't be purely numeric or date
        if (preg_match('/^\d+([.,]\d+)?$/', $line)) {
            return false;
        }

        return true;
    }

    /**
     * Clean up merchant name
     */
    private function cleanMerchantName(string $name): string
    {
        // Remove common suffixes/prefixes
        $name = preg_replace('/\s*(SDN\.?\s*BHD\.?|BERHAD|LTD\.?|LIMITED)\s*$/i', '', $name);
        $name = preg_replace('/^\s*(TAX\s*INVOICE|INVOICE)\s*/i', '', $name);

        return trim($name);
    }

    /**
     * Extract total amount from receipt
     *
     * @param array<string> $lines
     */
    private function extractAmount(string $text, array $lines): ?float
    {
        // First, try to find amount with keywords (more reliable)
        $amount = $this->extractAmountWithKeywords($lines);
        if ($amount !== null) {
            return $amount;
        }

        // Fallback to general amount patterns
        return $this->extractAmountWithPatterns($text);
    }

    /**
     * Extract amount using keyword-based approach
     *
     * @param array<string> $lines
     */
    private function extractAmountWithKeywords(array $lines): ?float
    {
        $amounts = [];

        foreach ($lines as $line) {
            $upperLine = strtoupper($line);

            foreach (self::AMOUNT_KEYWORDS as $keyword) {
                if (str_contains($upperLine, $keyword)) {
                    // Look for amount patterns on this line
                    if (preg_match('/RM\s*([0-9,]+\.\d{2})/i', $line, $matches)) {
                        $amount = (float) str_replace(',', '', $matches[1]);
                        $amounts[] = ['amount' => $amount, 'keyword' => $keyword];
                    } elseif (preg_match('/([0-9,]+\.\d{2})\s*RM/i', $line, $matches)) {
                        $amount = (float) str_replace(',', '', $matches[1]);
                        $amounts[] = ['amount' => $amount, 'keyword' => $keyword];
                    } elseif (preg_match('/MYR\s*([0-9,]+\.\d{2})/i', $line, $matches)) {
                        $amount = (float) str_replace(',', '', $matches[1]);
                        $amounts[] = ['amount' => $amount, 'keyword' => $keyword];
                    } elseif (preg_match('/(?:TOTAL|JUMLAH)[\s:]*([0-9,]+\.\d{2})/i', $line, $matches)) {
                        $amount = (float) str_replace(',', '', $matches[1]);
                        $amounts[] = ['amount' => $amount, 'keyword' => $keyword];
                    }
                }
            }
        }

        if (empty($amounts)) {
            return null;
        }

        // Prioritize GRAND TOTAL or JUMLAH BESAR, then TOTAL/JUMLAH
        foreach ($amounts as $item) {
            if (in_array($item['keyword'], ['GRAND TOTAL', 'JUMLAH BESAR', 'AMOUNT DUE'])) {
                return $item['amount'];
            }
        }

        // Return the largest amount (usually the total)
        usort($amounts, fn ($a, $b) => $b['amount'] <=> $a['amount']);

        return $amounts[0]['amount'];
    }

    /**
     * Extract amount using regex patterns
     */
    private function extractAmountWithPatterns(string $text): ?float
    {
        $patterns = [
            // RM 45.50 or RM45.50
            '/RM\s*([0-9,]+\.\d{2})/i',
            // 45.50 RM
            '/([0-9,]+\.\d{2})\s*RM/i',
            // MYR 45.50
            '/MYR\s*([0-9,]+\.\d{2})/i',
            // Generic amount with 2 decimal places (be careful with this)
            '/\b([0-9]{1,3}(?:,[0-9]{3})*\.[0-9]{2})\b/',
        ];

        $amounts = [];

        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $text, $matches)) {
                foreach ($matches[1] as $match) {
                    $amount = (float) str_replace(',', '', $match);
                    if ($amount > 0 && $amount < 100000) { // Sanity check
                        $amounts[] = $amount;
                    }
                }
            }
        }

        if (empty($amounts)) {
            return null;
        }

        // Return the largest amount (most likely the total)
        return max($amounts);
    }

    /**
     * Extract date from receipt text
     */
    private function extractDate(string $text): ?string
    {
        $datePatterns = [
            // DD/MM/YYYY or DD-MM-YYYY
            '/(\d{1,2})[\/\-\.](\d{1,2})[\/\-\.](\d{4})/',
            // YYYY-MM-DD or YYYY/MM/DD
            '/(\d{4})[\/\-](\d{1,2})[\/\-](\d{1,2})/',
            // DD MMM YYYY (e.g., 12 Feb 2024)
            '/(\d{1,2})\s+(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)[a-z]*\s+(\d{4})/i',
            // DD MMMM YYYY (full month name)
            '/(\d{1,2})\s+(January|February|March|April|May|June|July|August|September|October|November|December)\s+(\d{4})/i',
        ];

        foreach ($datePatterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                return $this->normalizeDate($matches);
            }
        }

        return null;
    }

    /**
     * Normalize date to YYYY-MM-DD format
     *
     * @param array<string> $matches
     */
    private function normalizeDate(array $matches): string
    {
        // Check which pattern matched based on the year position
        if (strlen($matches[3]) === 4) {
            // DD/MM/YYYY or DD MMM YYYY
            $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
            $month = is_numeric($matches[2])
                ? str_pad($matches[2], 2, '0', STR_PAD_LEFT)
                : $this->monthNameToNumber($matches[2]);
            $year = $matches[3];
        } else {
            // YYYY-MM-DD
            $year = $matches[1];
            $month = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
            $day = str_pad($matches[3], 2, '0', STR_PAD_LEFT);
        }

        return "{$year}-{$month}-{$day}";
    }

    /**
     * Convert month name to number
     */
    private function monthNameToNumber(string $monthName): string
    {
        $months = [
            'jan' => '01', 'january' => '01',
            'feb' => '02', 'february' => '02',
            'mar' => '03', 'march' => '03',
            'apr' => '04', 'april' => '04',
            'may' => '05',
            'jun' => '06', 'june' => '06',
            'jul' => '07', 'july' => '07',
            'aug' => '08', 'august' => '08',
            'sep' => '09', 'sept' => '09', 'september' => '09',
            'oct' => '10', 'october' => '10',
            'nov' => '11', 'november' => '11',
            'dec' => '12', 'december' => '12',
        ];

        $lower = strtolower($monthName);

        return $months[$lower] ?? '01';
    }

    /**
     * Extract item lines from receipt
     *
     * @param array<string> $lines
     * @return array<array<string, mixed>>
     */
    private function extractItems(array $lines): array
    {
        $items = [];
        $inItemSection = false;

        foreach ($lines as $line) {
            // Try to detect item lines
            // Common patterns:
            // - Item description followed by price
            // - Quantity x Price = Total

            $item = $this->parseItemLine($line);
            if ($item !== null) {
                $items[] = $item;
            }
        }

        return $items;
    }

    /**
     * Parse a single line as an item
     *
     * @return array<string, mixed>|null
     */
    private function parseItemLine(string $line): ?array
    {
        // Skip lines that are clearly not items
        if ($this->shouldSkipItemLine($line)) {
            return null;
        }

        // Pattern: description ... RM X.XX or X.XX
        // Look for price at the end of the line
        if (preg_match('/^(.+?)\s+RM\s*([0-9,]+\.\d{2})\s*$/i', $line, $matches)) {
            $description = trim($matches[1]);
            $price = (float) str_replace(',', '', $matches[2]);

            if ($this->isValidItemDescription($description)) {
                return [
                    'description' => $description,
                    'price' => $price,
                    'quantity' => 1,
                ];
            }
        }

        // Pattern: description ... X.XX (without RM)
        if (preg_match('/^(.+?)\s+([0-9]{1,3}(?:,[0-9]{3})*\.\d{2})\s*$/', $line, $matches)) {
            $description = trim($matches[1]);
            $price = (float) str_replace(',', '', $matches[2]);

            // Make sure description doesn't look like a total line
            if ($this->isValidItemDescription($description) && ! $this->isTotalLine($line)) {
                return [
                    'description' => $description,
                    'price' => $price,
                    'quantity' => 1,
                ];
            }
        }

        // Pattern: Qty x Item @ Price = Total
        if (preg_match('/(\d+)\s*[xX@]\s*.*?RM\s*([0-9,]+\.\d{2})/i', $line, $matches)) {
            // This is a complex line, try to extract
            $quantity = (int) $matches[1];

            // Extract description (everything before the quantity/price pattern)
            if (preg_match('/^(.+?)\s*\d+\s*[xX@]/', $line, $descMatches)) {
                $description = trim($descMatches[1]);

                return [
                    'description' => $description,
                    'price' => (float) str_replace(',', '', $matches[2]) * $quantity,
                    'quantity' => $quantity,
                ];
            }
        }

        return null;
    }

    /**
     * Check if line should be skipped as an item
     */
    private function shouldSkipItemLine(string $line): bool
    {
        $upperLine = strtoupper($line);

        // Skip total lines
        foreach (self::AMOUNT_KEYWORDS as $keyword) {
            if (str_contains($upperLine, $keyword)) {
                return true;
            }
        }

        // Skip header/footer lines
        $skipPatterns = [
            'ITEM', 'DESCRIPTION', 'PRICE', 'QTY', 'QUANTITY', 'AMOUNT',
            'THANK YOU', 'TERIMA KASIH', 'GST', 'SST', 'TAX',
            'CASH', 'CHANGE', 'BALANCE', 'CREDIT CARD', 'DEBIT CARD',
            'E-WALLET', 'TNG', 'TOUCH N GO', 'GRABPAY',
        ];

        foreach ($skipPatterns as $pattern) {
            if (str_contains($upperLine, $pattern)) {
                return true;
            }
        }

        // Skip if too short
        if (strlen($line) < 5) {
            return true;
        }

        return false;
    }

    /**
     * Check if description looks valid for an item
     */
    private function isValidItemDescription(string $description): bool
    {
        // Must have some letters
        if (! preg_match('/[a-zA-Z]/', $description)) {
            return false;
        }

        // Length check
        $length = strlen($description);
        if ($length < 3 || $length > 100) {
            return false;
        }

        return true;
    }

    /**
     * Check if line looks like a total/tax line
     */
    private function isTotalLine(string $line): bool
    {
        $upperLine = strtoupper($line);

        $totalIndicators = ['TOTAL', 'JUMLAH', 'TAX', 'GST', 'SST', 'SUBTOTAL'];

        foreach ($totalIndicators as $indicator) {
            if (str_contains($upperLine, $indicator)) {
                return true;
            }
        }

        return false;
    }
}
