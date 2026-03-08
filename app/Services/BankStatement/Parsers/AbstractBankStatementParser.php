<?php

declare(strict_types=1);

namespace App\Services\BankStatement\Parsers;

use App\Services\BankStatement\Builders\ParsedStatementBuilder;
use App\Services\BankStatement\Builders\StatementLineBuilder;
use App\Services\BankStatement\Contracts\BankStatementParser;
use App\Services\BankStatement\Dtos\ParsedStatement;
use App\Services\BankStatement\Dtos\StatementLine;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Smalot\PdfParser\Parser as PdfParser;

abstract class AbstractBankStatementParser implements BankStatementParser
{
    /**
     * @var array<string, Regex>
     */
    protected array $regexMap = [];

    protected ParsedStatementBuilder $parsedStatementBuilder;

    protected StatementLineBuilder $statementLineBuilder;

    protected ?PdfParser $pdfParser = null;

    public function __construct(
        ?ParsedStatementBuilder $parsedStatementBuilder = null,
        ?StatementLineBuilder $statementLineBuilder = null,
    ) {
        $this->parsedStatementBuilder = $parsedStatementBuilder ?? new ParsedStatementBuilder;
        $this->statementLineBuilder = $statementLineBuilder ?? new StatementLineBuilder;
        $this->configure();
    }

    /**
     * Configure the regex patterns for this parser.
     */
    abstract protected function configure(): void;

    /**
     * Extract text content from a PDF file.
     *
     * @throws \Exception
     */
    protected function extractTextFromPdf(string $filePath): string
    {
        if (! file_exists($filePath)) {
            throw new \InvalidArgumentException("File not found: {$filePath}");
        }

        if ($this->pdfParser === null) {
            $this->pdfParser = new PdfParser;
        }

        $pdf = $this->pdfParser->parseFile($filePath);

        return $pdf->getText();
    }

    /**
     * Parse multiple files in a directory.
     *
     * @return Collection<int, ParsedStatement>
     */
    public function parseDirectory(string $directoryPath): Collection
    {
        $outputs = new Collection;
        $filesystem = new Filesystem;

        if (! is_dir($directoryPath)) {
            Log::warning("Directory not found: {$directoryPath}");

            return $outputs;
        }

        $files = $filesystem->files($directoryPath);

        foreach ($files as $file) {
            if ($file->getExtension() === 'pdf') {
                try {
                    $output = $this->parseFile($file->getRealPath());
                    $outputs->push($output);
                } catch (\Exception $e) {
                    Log::error("Failed to parse file {$file->getFilename()}: ".$e->getMessage());
                }
            }
        }

        return $outputs;
    }

    /**
     * Process a single regex match against a line.
     *
     * @param  array<int, string>|null  $matches
     * @return array<string, string>
     */
    protected function processSingleRegexMatches(?array $matches, SingleRegex $singleRegex): array
    {
        $results = [];

        if ($matches === null) {
            return $results;
        }

        foreach ($singleRegex->getValueMapping() as $groupIndex => $key) {
            if (isset($matches[$groupIndex])) {
                $results[$key] = $matches[$groupIndex];
            }
        }

        return $results;
    }

    /**
     * Process a statement regex match.
     *
     * @param  array<int, string>|null  $matches
     */
    protected function processStatementRegexMatches(?array $matches, StatementRegex $statementRegex): ?StatementLine
    {
        if ($matches === null || count($matches) < $statementRegex->getMatchesCount()) {
            return null;
        }

        $line = $this->statementLineBuilder
            ->withPostingDate($matches[1] ?? null)
            ->withTransactionDate($matches[2] ?? null)
            ->withDescription($matches[3] ?? null)
            ->withAmount($matches[4] ?? null)
            ->withCredit($statementRegex->isCredit())
            ->build();

        $this->statementLineBuilder->reset();

        return $line;
    }

    /**
     * Add a regex pattern to the parser.
     */
    protected function addRegex(string $key, Regex $regex): void
    {
        $this->regexMap[$key] = $regex;
    }

    /**
     * Get a regex pattern by key.
     */
    protected function getRegex(string $key): ?Regex
    {
        return $this->regexMap[$key] ?? null;
    }

    /**
     * Match a regex pattern against text.
     *
     * @return array<int, string>|null
     */
    protected function matchRegex(string $pattern, string $text): ?array
    {
        $matches = [];
        if (preg_match('/'.$pattern.'/u', $text, $matches)) {
            return $matches;
        }

        return null;
    }

    /**
     * Check if text matches a regex pattern.
     */
    protected function matchesRegex(string $pattern, string $text): bool
    {
        return preg_match('/'.$pattern.'/u', $text) === 1;
    }

    /**
     * Extract the first match group.
     */
    protected function extractMatch(string $pattern, string $text): ?string
    {
        $matches = $this->matchRegex($pattern, $text);

        return $matches[1] ?? null;
    }

    /**
     * Parse statement date and extract month and year.
     *
     * @param  string  $dateString  Format: "dd MMM yy"
     * @return array{month: int, year: int}|null
     */
    protected function parseStatementDate(string $dateString): ?array
    {
        $timestamp = strtotime($dateString);

        if ($timestamp === false) {
            return null;
        }

        return [
            'month' => (int) date('n', $timestamp),
            'year' => (int) date('Y', $timestamp),
        ];
    }
}
