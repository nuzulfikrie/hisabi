<?php

declare(strict_types=1);

namespace App\Services\BankStatement\Resolvers;

use App\Services\BankStatement\Contracts\BankStatementParser;
use App\Services\BankStatement\Parsers\CimbStatementParser;
use App\Services\BankStatement\Parsers\MaybankStatementParser;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class BankStatementResolver
{
    /**
     * @var Collection<int, BankStatementParser>
     */
    protected Collection $parsers;

    public function __construct()
    {
        $this->parsers = new Collection;
        $this->registerDefaultParsers();
    }

    /**
     * Register the default bank statement parsers.
     */
    protected function registerDefaultParsers(): void
    {
        $this->registerParser(new MaybankStatementParser);
        $this->registerParser(new CimbStatementParser);
    }

    /**
     * Register a new parser.
     */
    public function registerParser(BankStatementParser $parser): self
    {
        $this->parsers->push($parser);

        return $this;
    }

    /**
     * Get all registered parsers.
     *
     * @return Collection<int, BankStatementParser>
     */
    public function getParsers(): Collection
    {
        return $this->parsers;
    }

    /**
     * Resolve the appropriate parser for a given file.
     *
     * @throws InvalidArgumentException
     */
    public function resolve(string $filePath): BankStatementParser
    {
        if (! file_exists($filePath)) {
            throw new InvalidArgumentException("File not found: {$filePath}");
        }

        $content = $this->extractContentForIdentification($filePath);

        foreach ($this->parsers as $parser) {
            if ($parser->supports($content)) {
                return $parser;
            }
        }

        throw new InvalidArgumentException(
            'No parser found for the given file. Unsupported bank statement format.'
        );
    }

    /**
     * Resolve parser by bank identifier.
     *
     * @throws InvalidArgumentException
     */
    public function resolveByIdentifier(string $identifier): BankStatementParser
    {
        foreach ($this->parsers as $parser) {
            if ($parser->getBankIdentifier() === $identifier) {
                return $parser;
            }
        }

        throw new InvalidArgumentException(
            "No parser found for bank identifier: {$identifier}"
        );
    }

    /**
     * Check if a parser exists for the given file.
     */
    public function canParse(string $filePath): bool
    {
        try {
            $this->resolve($filePath);

            return true;
        } catch (InvalidArgumentException) {
            return false;
        }
    }

    /**
     * Check if a parser exists for the given bank identifier.
     */
    public function hasParser(string $identifier): bool
    {
        foreach ($this->parsers as $parser) {
            if ($parser->getBankIdentifier() === $identifier) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get all available bank identifiers.
     *
     * @return array<int, array{identifier: string, name: string}>
     */
    public function getAvailableBanks(): array
    {
        return $this->parsers->map(fn (BankStatementParser $parser) => [
            'identifier' => $parser->getBankIdentifier(),
            'name' => $parser->getBankName(),
        ])->toArray();
    }

    /**
     * Extract content from file for identification purposes.
     */
    protected function extractContentForIdentification(string $filePath): string
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if ($extension === 'pdf') {
            return $this->extractPdfContent($filePath);
        }

        // For text-based files
        $content = file_get_contents($filePath);

        return $content !== false ? $content : '';
    }

    /**
     * Extract text content from a PDF file.
     */
    protected function extractPdfContent(string $filePath): string
    {
        try {
            $parser = new \Smalot\PdfParser\Parser;
            $pdf = $parser->parseFile($filePath);

            return $pdf->getText();
        } catch (\Exception $e) {
            return '';
        }
    }
}
