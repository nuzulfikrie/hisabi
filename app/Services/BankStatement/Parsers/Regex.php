<?php

declare(strict_types=1);

namespace App\Services\BankStatement\Parsers;

abstract class Regex
{
    protected ?string $regex = null;

    protected int $matchesCount = 0;

    public function getRegex(): ?string
    {
        return $this->regex;
    }

    public function setRegex(?string $regex): void
    {
        $this->regex = $regex;
    }

    public function getMatchesCount(): int
    {
        return $this->matchesCount;
    }

    public function setMatchesCount(int $matchesCount): void
    {
        $this->matchesCount = $matchesCount;
    }
}
