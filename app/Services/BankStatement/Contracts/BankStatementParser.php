<?php

declare(strict_types=1);

namespace App\Services\BankStatement\Contracts;

use App\Services\BankStatement\Dtos\ParsedStatement;
use Illuminate\Support\Collection;

interface BankStatementParser
{
    /**
     * Check if this parser supports the given file content.
     */
    public function supports(string $content): bool;

    /**
     * Parse a single file and return the parsed statement.
     */
    public function parseFile(string $filePath): ParsedStatement;

    /**
     * Parse multiple files in a directory.
     *
     * @return Collection<int, ParsedStatement>
     */
    public function parseDirectory(string $directoryPath): Collection;

    /**
     * Get the bank identifier for this parser.
     */
    public function getBankIdentifier(): string;

    /**
     * Get the bank name for this parser.
     */
    public function getBankName(): string;
}
