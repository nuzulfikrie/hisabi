<?php

declare(strict_types=1);

namespace App\Services\BankStatement\Dtos;

use Illuminate\Support\Collection;

class ParsedStatement
{
    /**
     * @var array<string, string>
     */
    private array $mainValues = [];

    /**
     * @var array<string, array<string, string>>
     */
    private array $specificValues = [];

    /**
     * @var array<string, Collection<int, StatementLine>>
     */
    private array $statements = [];

    private ?int $statementMonth = null;

    private ?int $statementYear = null;

    private ?string $sourceFile = null;

    public function getMainValue(string $key): ?string
    {
        return $this->mainValues[$key] ?? null;
    }

    public function setMainValue(string $key, string $value): void
    {
        $this->mainValues[$key] = $value;
    }

    /**
     * @return array<string, string>
     */
    public function getMainValues(): array
    {
        return $this->mainValues;
    }

    /**
     * @param  array<string, string>  $values
     */
    public function setMainValues(array $values): void
    {
        $this->mainValues = $values;
    }

    public function getSpecificValue(string $card, string $key): ?string
    {
        return $this->specificValues[$card][$key] ?? null;
    }

    public function setSpecificValue(string $card, string $key, string $value): void
    {
        if (! isset($this->specificValues[$card])) {
            $this->specificValues[$card] = [];
        }
        $this->specificValues[$card][$key] = $value;
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function getSpecificValues(): array
    {
        return $this->specificValues;
    }

    /**
     * @param  array<string, array<string, string>>  $values
     */
    public function setSpecificValues(array $values): void
    {
        $this->specificValues = $values;
    }

    /**
     * @return Collection<int, StatementLine>
     */
    public function getStatementsForCard(string $card): Collection
    {
        return $this->statements[$card] ?? new Collection;
    }

    /**
     * @return array<string, Collection<int, StatementLine>>
     */
    public function getAllStatements(): array
    {
        return $this->statements;
    }

    public function addStatementLine(string $card, StatementLine $line): void
    {
        if (! isset($this->statements[$card])) {
            $this->statements[$card] = new Collection;
        }
        $this->statements[$card]->push($line);
    }

    public function getStatementMonth(): ?int
    {
        return $this->statementMonth;
    }

    public function setStatementMonth(?int $statementMonth): void
    {
        $this->statementMonth = $statementMonth;
    }

    public function getStatementYear(): ?int
    {
        return $this->statementYear;
    }

    public function setStatementYear(?int $statementYear): void
    {
        $this->statementYear = $statementYear;
    }

    public function getSourceFile(): ?string
    {
        return $this->sourceFile;
    }

    public function setSourceFile(?string $sourceFile): void
    {
        $this->sourceFile = $sourceFile;
    }

    /**
     * Get the statement month as zero-padded string (01-12).
     */
    public function getPaddedMonth(): string
    {
        return sprintf('%02d', $this->statementMonth ?? 0);
    }

    /**
     * Get all statement lines for all cards.
     *
     * @return Collection<int, StatementLine>
     */
    public function getAllStatementLines(): Collection
    {
        $allLines = new Collection;
        foreach ($this->statements as $lines) {
            $allLines = $allLines->merge($lines);
        }

        return $allLines;
    }

    /**
     * Convert to array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $statementsArray = [];
        foreach ($this->statements as $card => $lines) {
            $statementsArray[$card] = $lines->map(fn (StatementLine $line) => $line->toArray())->toArray();
        }

        return [
            'statement_month' => $this->statementMonth,
            'statement_year' => $this->statementYear,
            'main_values' => $this->mainValues,
            'specific_values' => $this->specificValues,
            'statements' => $statementsArray,
            'source_file' => $this->sourceFile,
        ];
    }
}
