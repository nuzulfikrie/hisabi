<?php

declare(strict_types=1);

namespace App\Services\BankStatement\Parsers;

use App\Enums\CimbDepositCategory;
use App\Enums\CimbExpenseCategory;
use App\Services\BankStatement\Dtos\CimbStatementLine;
use App\Services\BankStatement\Dtos\ParsedStatement;

class CimbStatementParser extends AbstractBankStatementParser
{
    private const BANK_IDENTIFIER = 'cimb';

    private const BANK_NAME = 'CIMB Bank';

    /**
     * Pattern to identify CIMB statements.
     */
    private const IDENTIFICATION_PATTERN = 'CIMB|ACCOUNT STATEMENT|Statement Date';

    protected function configure(): void
    {
        // CIMB uses table-based extraction, not regex patterns
        $this->regexMap = [];
    }

    public function supports(string $content): bool
    {
        return $this->matchesRegex(self::IDENTIFICATION_PATTERN, $content);
    }

    public function parseFile(string $filePath): ParsedStatement
    {
        $this->parsedStatementBuilder->reset();

        if (! file_exists($filePath)) {
            throw new \InvalidArgumentException("File not found: {$filePath}");
        }

        $this->parsedStatementBuilder->withSourceFile($filePath);

        // Extract tables from PDF using pdfparser
        $lines = $this->extractTableData($filePath);

        foreach ($lines as $row) {
            $this->processRow($row);
        }

        return $this->parsedStatementBuilder->build();
    }

    /**
     * Extract table data from PDF.
     *
     * @return array<int, array<int, string|null>>
     */
    protected function extractTableData(string $filePath): array
    {
        $content = $this->extractTextFromPdf($filePath);

        // Parse the content into rows
        // CIMB statements typically have tabular data
        $lines = [];
        $rawLines = explode("\n", $content);

        foreach ($rawLines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            // Try to parse as transaction row
            // Format: Date | Description | Withdrawal | Deposit | Balance
            $parsed = $this->parseTransactionLine($line);
            if ($parsed !== null) {
                $lines[] = $parsed;
            }
        }

        return $lines;
    }

    /**
     * Parse a transaction line from CIMB statement.
     *
     * @return array<int, string|null>|null
     */
    protected function parseTransactionLine(string $line): ?array
    {
        // Date pattern: DD/MM/YYYY (using # as delimiter to avoid escaping slashes)
        $datePattern = '(\d{2}/\d{2}/\d{4})';

        if (! preg_match('#^'.$datePattern.'#', $line, $matches)) {
            return null;
        }

        $dateStr = $matches[1];
        $remaining = substr($line, strlen($dateStr));

        // Try to extract numeric values (amounts and balance)
        // CIMB format typically has: Date Description Withdrawal Deposit Balance
        preg_match_all('/[\d,]+\.\d{2}/', $remaining, $amountMatches);

        /** @var array<int, string> $amounts */
        $amounts = $amountMatches[0];

        // Extract description (text between date and amounts)
        $description = $remaining;
        foreach ($amounts as $amount) {
            $description = str_replace($amount, '', $description);
        }
        $description = trim(preg_replace('/\s+/', ' ', $description));

        // Determine withdrawal, deposit, and balance based on amounts
        $withdrawal = null;
        $deposit = null;
        $balance = null;

        if (count($amounts) >= 2) {
            // Last amount is usually balance
            $balance = array_pop($amounts);

            // First amount could be withdrawal or deposit
            // Check description or context to determine
            if (count($amounts) === 2) {
                $withdrawal = $amounts[0];
                $deposit = $amounts[1];
            } elseif (count($amounts) === 1) {
                // Single amount - determine if withdrawal or deposit based on context
                $withdrawal = $amounts[0];
            }
        } elseif (count($amounts) === 1) {
            // Only balance available
            $balance = $amounts[0];
        }

        return [
            $dateStr,
            $description,
            $withdrawal,
            $deposit,
            $balance,
        ];
    }

    /**
     * Process a row from the table.
     *
     * @param  array<int, string|null>  $row
     */
    protected function processRow(array $row): void
    {
        // Check if row is valid
        if (count($row) < 2) {
            return;
        }

        // Extract date components
        $dateStr = $row[0] ?? '';
        $dateComponents = explode('/', $dateStr);

        if (count($dateComponents) < 3) {
            return;
        }

        $day = $dateComponents[0];
        $monthNum = (int) $dateComponents[1];
        $year = $dateComponents[2];
        $month = $this->getMonthName($monthNum);

        // Extract values
        $description = $row[1] ?? '';
        $description = str_replace("\n", ' ', $description);
        $withdrawal = $row[2] ?? '';
        $deposit = $row[3] ?? '';
        $balance = $row[4] ?? '';

        // Skip rows without valid amounts
        if (empty($withdrawal) && empty($deposit)) {
            return;
        }

        // Create statement line
        $line = new CimbStatementLine;
        $line->setTransactionDate($dateStr);
        $line->setDescription($description);
        $line->setYear($year);
        $line->setMonth($month);
        $line->setDay($day);
        $line->setBalance($balance);
        $line->setExpense($withdrawal);
        $line->setDeposit($deposit);

        // Categorize based on description
        $expenseCategory = $this->categorizeExpense($description, $withdrawal);
        $depositCategory = $this->categorizeDeposit($description);

        $line->setExpenseCategory($expenseCategory);
        $line->setDepositCategory($depositCategory);

        // Set credit/debit flags
        $line->setCredit(! empty($deposit));
        $line->setAmount(! empty($deposit) ? $deposit : $withdrawal);

        // Add to parsed statement using a default account identifier
        $this->parsedStatementBuilder->addStatementLine('primary', $line);

        // Track statement date info
        if ($year !== '' && $monthNum > 0) {
            $this->parsedStatementBuilder->withStatementMonth($monthNum);
            $this->parsedStatementBuilder->withStatementYear((int) $year);
        }
    }

    /**
     * Categorize expense based on description.
     */
    protected function categorizeExpense(string $description, string $expense): ?string
    {
        if (empty($expense)) {
            return null;
        }

        $category = CimbExpenseCategory::match($description);

        return $category === CimbExpenseCategory::UNCATEGORIZED
            ? null
            : $category->value;
    }

    /**
     * Categorize deposit based on description.
     */
    protected function categorizeDeposit(string $description): ?string
    {
        $category = CimbDepositCategory::match($description);

        return $category === CimbDepositCategory::UNCATEGORIZED
            ? null
            : $category->value;
    }

    /**
     * Get month name from month number.
     */
    protected function getMonthName(int $monthNum): string
    {
        $monthNames = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
            5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug',
            9 => 'Sept', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec',
        ];

        return $monthNames[$monthNum] ?? '';
    }

    /**
     * Generate analysis report similar to step4_calc.py.
     *
     * @param  array<int, ParsedStatement>  $statements
     * @return array<string, array<string, mixed>>
     */
    public function generateAnalysis(array $statements): array
    {
        $allLines = [];
        foreach ($statements as $statement) {
            foreach ($statement->getAllStatements() as $card => $lines) {
                foreach ($lines as $line) {
                    if ($line instanceof CimbStatementLine) {
                        $allLines[] = $line;
                    }
                }
            }
        }

        return [
            'expenses_by_year' => $this->calculateExpensesByYear($allLines),
            'expenses_by_category' => $this->calculateExpensesByCategory($allLines),
            'expenses_by_year_month' => $this->calculateExpensesByYearMonth($allLines),
            'expenses_by_year_category' => $this->calculateExpensesByYearCategory($allLines),
            'deposits_by_year' => $this->calculateDepositsByYear($allLines),
            'deposits_by_category' => $this->calculateDepositsByCategory($allLines),
        ];
    }

    /**
     * Calculate total expenses by year.
     *
     * @param  array<int, CimbStatementLine>  $lines
     * @return array<string, float>
     */
    protected function calculateExpensesByYear(array $lines): array
    {
        $result = [];

        foreach ($lines as $line) {
            $year = $line->getYear();
            $expense = $this->parseAmount($line->getExpense());

            if ($year !== null && $expense !== null) {
                $result[$year] = ($result[$year] ?? 0) + $expense;
            }
        }

        return $result;
    }

    /**
     * Calculate total expenses by category.
     *
     * @param  array<int, CimbStatementLine>  $lines
     * @return array<string, float>
     */
    protected function calculateExpensesByCategory(array $lines): array
    {
        $result = [];

        foreach ($lines as $line) {
            $category = $line->getExpenseCategory();
            $expense = $this->parseAmount($line->getExpense());

            if (! empty($category) && $expense !== null) {
                $result[$category] = ($result[$category] ?? 0) + $expense;
            }
        }

        return $result;
    }

    /**
     * Calculate total expenses by year and month.
     *
     * @param  array<int, CimbStatementLine>  $lines
     * @return array<string, float>
     */
    protected function calculateExpensesByYearMonth(array $lines): array
    {
        $result = [];

        foreach ($lines as $line) {
            $year = $line->getYear();
            $month = $line->getMonth();
            $expense = $this->parseAmount($line->getExpense());

            if ($year !== null && $month !== null && $expense !== null) {
                $key = $year.'_'.$month;
                $result[$key] = ($result[$key] ?? 0) + $expense;
            }
        }

        return $result;
    }

    /**
     * Calculate total expenses by year and category.
     *
     * @param  array<int, CimbStatementLine>  $lines
     * @return array<string, array<string, float>>
     */
    protected function calculateExpensesByYearCategory(array $lines): array
    {
        $result = [];

        foreach ($lines as $line) {
            $year = $line->getYear();
            $category = $line->getExpenseCategory();
            $expense = $this->parseAmount($line->getExpense());

            if ($year !== null && ! empty($category) && $expense !== null) {
                $result[$year][$category] = ($result[$year][$category] ?? 0) + $expense;
            }
        }

        return $result;
    }

    /**
     * Calculate total deposits by year.
     *
     * @param  array<int, CimbStatementLine>  $lines
     * @return array<string, float>
     */
    protected function calculateDepositsByYear(array $lines): array
    {
        $result = [];

        foreach ($lines as $line) {
            $year = $line->getYear();
            $deposit = $this->parseAmount($line->getDeposit());

            if ($year !== null && $deposit !== null) {
                $result[$year] = ($result[$year] ?? 0) + $deposit;
            }
        }

        return $result;
    }

    /**
     * Calculate total deposits by category.
     *
     * @param  array<int, CimbStatementLine>  $lines
     * @return array<string, float>
     */
    protected function calculateDepositsByCategory(array $lines): array
    {
        $result = [];

        foreach ($lines as $line) {
            $category = $line->getDepositCategory();
            $deposit = $this->parseAmount($line->getDeposit());

            if (! empty($category) && $deposit !== null) {
                $result[$category] = ($result[$category] ?? 0) + $deposit;
            }
        }

        return $result;
    }

    /**
     * Parse amount string to float.
     */
    protected function parseAmount(?string $amount): ?float
    {
        if (empty($amount)) {
            return null;
        }

        $cleaned = str_replace(',', '', $amount);

        return is_numeric($cleaned) ? (float) $cleaned : null;
    }

    public function getBankIdentifier(): string
    {
        return self::BANK_IDENTIFIER;
    }

    public function getBankName(): string
    {
        return self::BANK_NAME;
    }
}
