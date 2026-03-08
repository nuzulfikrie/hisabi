<?php

declare(strict_types=1);

namespace App\Services\BankStatement\Parsers;

class SingleRegex extends Regex
{
    /**
     * Whether the actual value is in current line, or next line.
     */
    private bool $isCurrent = false;

    /**
     * Whether value of this regex is tied to specific card.
     */
    private bool $isSpecificCard = false;

    /**
     * If isCurrent = false, parse value in the next line using this regex.
     */
    private ?string $regexNext = null;

    /**
     * Map regex match index to its value mapping.
     *
     * @var array<int, string>
     */
    private array $valueMapping = [];

    public function isCurrent(): bool
    {
        return $this->isCurrent;
    }

    public function setCurrent(bool $current): void
    {
        $this->isCurrent = $current;
    }

    public function getRegexNext(): ?string
    {
        return $this->regexNext;
    }

    public function setRegexNext(?string $regexNext): void
    {
        $this->regexNext = $regexNext;
    }

    /**
     * @return array<int, string>
     */
    public function getValueMapping(): array
    {
        return $this->valueMapping;
    }

    /**
     * @param  array<int, string>  $valueMapping
     */
    public function setValueMapping(array $valueMapping): void
    {
        $this->valueMapping = $valueMapping;
    }

    public function addValueMapping(int $index, string $key): void
    {
        $this->valueMapping[$index] = $key;
    }

    public function isSpecificCard(): bool
    {
        return $this->isSpecificCard;
    }

    public function setSpecificCard(bool $specificCard): void
    {
        $this->isSpecificCard = $specificCard;
    }
}
