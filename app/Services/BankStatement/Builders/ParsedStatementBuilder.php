<?php

declare(strict_types=1);

namespace App\Services\BankStatement\Builders;

use App\Services\BankStatement\Dtos\ParsedStatement;
use App\Services\BankStatement\Dtos\StatementLine;
use Illuminate\Support\Collection;

class ParsedStatementBuilder
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

    /**
     * @param  array<string, string>  $values
     */
    public function withMainValues(array $values): self
    {
        $this->mainValues = $values;

        return $this;
    }

    public function withMainValue(string $key, string $value): self
    {
        $this->mainValues[$key] = $value;

        return $this;
    }

    /**
     * @param  array<string, array<string, string>>  $values
     */
    public function withSpecificValues(array $values): self
    {
        $this->specificValues = $values;

        return $this;
    }

    public function withSpecificValue(string $card, string $key, string $value): self
    {
        if (! isset($this->specificValues[$card])) {
            $this->specificValues[$card] = [];
        }
        $this->specificValues[$card][$key] = $value;

        return $this;
    }

    /**
     * @param  array<string, Collection<int, StatementLine>>  $statements
     */
    public function withStatements(array $statements): self
    {
        $this->statements = $statements;

        return $this;
    }

    public function addStatementLine(string $card, StatementLine $line): self
    {
        if (! isset($this->statements[$card])) {
            $this->statements[$card] = new Collection;
        }
        $this->statements[$card]->push($line);

        return $this;
    }

    public function withStatementMonth(?int $month): self
    {
        $this->statementMonth = $month;

        return $this;
    }

    public function withStatementYear(?int $year): self
    {
        $this->statementYear = $year;

        return $this;
    }

    public function withSourceFile(?string $sourceFile): self
    {
        $this->sourceFile = $sourceFile;

        return $this;
    }

    public function build(): ParsedStatement
    {
        $parsedStatement = new ParsedStatement;
        $parsedStatement->setMainValues($this->mainValues);
        $parsedStatement->setSpecificValues($this->specificValues);
        $parsedStatement->setStatementMonth($this->statementMonth);
        $parsedStatement->setStatementYear($this->statementYear);
        $parsedStatement->setSourceFile($this->sourceFile);

        foreach ($this->statements as $card => $lines) {
            foreach ($lines as $line) {
                $parsedStatement->addStatementLine($card, $line);
            }
        }

        return $parsedStatement;
    }

    public function reset(): self
    {
        $this->mainValues = [];
        $this->specificValues = [];
        $this->statements = [];
        $this->statementMonth = null;
        $this->statementYear = null;
        $this->sourceFile = null;

        return $this;
    }
}
