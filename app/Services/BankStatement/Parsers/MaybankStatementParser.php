<?php

declare(strict_types=1);

namespace App\Services\BankStatement\Parsers;

use App\Services\BankStatement\Dtos\ParsedStatement;
use Illuminate\Support\Facades\Log;

class MaybankStatementParser extends AbstractBankStatementParser
{
    private const BANK_IDENTIFIER = 'maybank';

    private const BANK_NAME = 'Maybank';

    /**
     * Pattern to identify Maybank statements.
     */
    private const IDENTIFICATION_PATTERN = 'Account Number\/Nombor Akaun|Maybank|Tarikh Akhir Pembayaran';

    /**
     * Pattern to extract current credit card from line.
     */
    private const CREDIT_CARD_PATTERN = '.+\s\d{4}+\s+\d{4}+\s+\d{4}+\s+(\d{4}+)';

    protected function configure(): void
    {
        $this->configureSummaryRegex();
        $this->configureStatementMonthRegex();
        $this->configurePreviousBalanceRegex();
        $this->configureStatementLineRegexes();
        $this->configureTotalCreditRegex();
        $this->configureTotalDebitRegex();
    }

    private function configureSummaryRegex(): void
    {
        $singleRegex = new SingleRegex;
        $singleRegex->setRegex('Account Number\\/Nombor Akaun Current Balance.+');
        $singleRegex->setCurrent(false);
        $singleRegex->setRegexNext('\d{4}+\s+\d{4}+\s+\d{4}+\s+(\d{4}+)\s+(.+)+\s(.+)');
        $singleRegex->addValueMapping(1, 'main_credit_card');
        $singleRegex->addValueMapping(2, 'main_current_account_balance');
        $singleRegex->addValueMapping(3, 'main_minimum_amount_to_pay');
        $singleRegex->setMatchesCount(3);

        $this->addRegex($singleRegex->getRegex(), $singleRegex);
    }

    private function configureStatementMonthRegex(): void
    {
        $monthRegex = new SingleRegex;
        $monthRegex->setRegex('Tarikh Akhir Pembayaran\s?');
        $monthRegex->setCurrent(false);
        $monthRegex->setRegexNext('(\d{2}\s+[A-Z]{3}\s+\d{2})\s+\d{2}\s+[A-Z]{3}\s+\d{2}');
        $monthRegex->addValueMapping(1, 'main_statement_month');
        $monthRegex->setMatchesCount(1);

        $this->addRegex($monthRegex->getRegex(), $monthRegex);
    }

    private function configurePreviousBalanceRegex(): void
    {
        $singleRegex = new SingleRegex;
        $singleRegex->setRegex('YOUR PREVIOUS STATEMENT BALANCE\s+(.+)');
        $singleRegex->setCurrent(true);
        $singleRegex->addValueMapping(1, 'main_previous_balance');
        $singleRegex->setMatchesCount(1);

        $this->addRegex($singleRegex->getRegex(), $singleRegex);
    }

    private function configureStatementLineRegexes(): void
    {
        // Debit statement regex
        $stmtRegexDebit = new StatementRegex;
        $stmtRegexDebit->setCredit(false);
        $stmtRegexDebit->setRegex('(\d{2}/\d{2})\s+(\d{2}/\d{2})\s+(.+)\s+([\d,]+\.\d{2})');
        $stmtRegexDebit->setMatchesCount(4);

        $this->addRegex($stmtRegexDebit->getRegex(), $stmtRegexDebit);

        // Credit statement regex
        $stmtRegexCredit = new StatementRegex;
        $stmtRegexCredit->setCredit(true);
        $stmtRegexCredit->setRegex('(\d{2}/\d{2})\s+(\d{2}/\d{2})\s+(.+)\s+([\d,]+\.\d{2})CR');
        $stmtRegexCredit->setMatchesCount(4);

        $this->addRegex($stmtRegexCredit->getRegex(), $stmtRegexCredit);
    }

    private function configureTotalCreditRegex(): void
    {
        $totalCredit = new SingleRegex;
        $totalCredit->setCurrent(false);
        $totalCredit->setRegex('\(JUMLAH KREDIT\)');
        $totalCredit->setRegexNext('(.+)');
        $totalCredit->setMatchesCount(1);
        $totalCredit->addValueMapping(1, 'total_credit');
        $totalCredit->setSpecificCard(true);

        $this->addRegex($totalCredit->getRegex(), $totalCredit);
    }

    private function configureTotalDebitRegex(): void
    {
        $totalDebit = new SingleRegex;
        $totalDebit->setCurrent(false);
        $totalDebit->setRegex('\(JUMLAH DEBIT\)');
        $totalDebit->setRegexNext('(.+)');
        $totalDebit->setMatchesCount(1);
        $totalDebit->addValueMapping(1, 'total_debit');
        $totalDebit->setSpecificCard(true);

        $this->addRegex($totalDebit->getRegex(), $totalDebit);
    }

    public function supports(string $content): bool
    {
        return $this->matchesRegex(self::IDENTIFICATION_PATTERN, $content);
    }

    public function parseFile(string $filePath): ParsedStatement
    {
        $this->parsedStatementBuilder->reset();

        $content = $this->extractTextFromPdf($filePath);
        $this->parsedStatementBuilder->withSourceFile($filePath);

        $currentCard = null;
        $isNextRegex = false;
        $nextRegex = null;
        $currentSingleRegex = null;

        $lines = explode("\n", $content);

        foreach ($lines as $line) {
            $line = trim($line);

            if (empty($line)) {
                continue;
            }

            if ($isNextRegex) {
                $this->processNextRegexLine($line, $currentCard, $nextRegex, $currentSingleRegex);
                $isNextRegex = false;
                $nextRegex = null;
                $currentSingleRegex = null;

                continue;
            }

            // Check if line contains current credit card
            $cardMatch = $this->extractMatch(self::CREDIT_CARD_PATTERN, $line);
            if ($cardMatch !== null) {
                $currentCard = $cardMatch;

                continue;
            }

            // Process other regex patterns
            $this->processLine($line, $currentCard, $isNextRegex, $nextRegex, $currentSingleRegex);
        }

        // Set statement month and year from main values
        $this->processStatementDate();

        return $this->parsedStatementBuilder->build();
    }

    /**
     * Process a line that is expected to match the next regex pattern.
     */
    private function processNextRegexLine(
        string $line,
        ?string $currentCard,
        ?string $nextRegex,
        ?SingleRegex $singleRegex,
    ): void {
        if ($nextRegex === null || $singleRegex === null) {
            return;
        }

        $matches = $this->matchRegex($nextRegex, $line);

        if ($matches === null) {
            return;
        }

        $results = $this->processSingleRegexMatches($matches, $singleRegex);

        foreach ($results as $key => $value) {
            if ($singleRegex->isSpecificCard()) {
                if ($currentCard !== null) {
                    $this->parsedStatementBuilder->withSpecificValue($currentCard, $key, $value);
                }
            } else {
                $this->parsedStatementBuilder->withMainValue($key, $value);
            }
        }
    }

    /**
     * Process a single line with configured regex patterns.
     *
     * @param  bool  $isNextRegex  Reference parameter
     * @param  ?string  $nextRegex  Reference parameter
     * @param  ?SingleRegex  $currentSingleRegex  Reference parameter
     */
    private function processLine(
        string $line,
        ?string $currentCard,
        bool &$isNextRegex,
        ?string &$nextRegex,
        ?SingleRegex &$currentSingleRegex,
    ): void {
        foreach ($this->regexMap as $regexPattern => $regex) {
            if (! $this->matchesRegex($regexPattern, $line)) {
                continue;
            }

            if ($regex instanceof SingleRegex) {
                $this->processSingleRegex($line, $currentCard, $regex, $isNextRegex, $nextRegex, $currentSingleRegex);
            } elseif ($regex instanceof StatementRegex) {
                $this->processStatementRegex($line, $currentCard, $regex);
            }

            break;
        }
    }

    /**
     * Process a SingleRegex pattern match.
     *
     * @param  bool  $isNextRegex  Reference parameter
     * @param  ?string  $nextRegex  Reference parameter
     * @param  ?SingleRegex  $currentSingleRegex  Reference parameter
     */
    private function processSingleRegex(
        string $line,
        ?string $currentCard,
        SingleRegex $singleRegex,
        bool &$isNextRegex,
        ?string &$nextRegex,
        ?SingleRegex &$currentSingleRegex,
    ): void {
        if ($singleRegex->isCurrent()) {
            $matches = $this->matchRegex($singleRegex->getRegex(), $line);
            $results = $this->processSingleRegexMatches($matches, $singleRegex);

            foreach ($results as $key => $value) {
                if ($singleRegex->isSpecificCard()) {
                    if ($currentCard !== null) {
                        $this->parsedStatementBuilder->withSpecificValue($currentCard, $key, $value);
                    }
                } else {
                    $this->parsedStatementBuilder->withMainValue($key, $value);
                }
            }
        } else {
            $isNextRegex = true;
            $nextRegex = $singleRegex->getRegexNext();
            $currentSingleRegex = $singleRegex;
        }
    }

    /**
     * Process a StatementRegex pattern match.
     */
    private function processStatementRegex(
        string $line,
        ?string $currentCard,
        StatementRegex $statementRegex,
    ): void {
        $matches = $this->matchRegex($statementRegex->getRegex(), $line);
        $statementLine = $this->processStatementRegexMatches($matches, $statementRegex);

        if ($statementLine !== null && $currentCard !== null) {
            $this->parsedStatementBuilder->addStatementLine($currentCard, $statementLine);
        }
    }

    /**
     * Process statement date from main values and set month/year.
     */
    private function processStatementDate(): void
    {
        $statement = $this->parsedStatementBuilder->build();
        $stmtMonthStr = $statement->getMainValue('main_statement_month');

        if ($stmtMonthStr === null) {
            return;
        }

        $dateInfo = $this->parseStatementDate($stmtMonthStr);

        if ($dateInfo === null) {
            Log::warning("Failed to parse statement date: {$stmtMonthStr}");

            return;
        }

        $this->parsedStatementBuilder
            ->withStatementMonth($dateInfo['month'])
            ->withStatementYear($dateInfo['year']);
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
