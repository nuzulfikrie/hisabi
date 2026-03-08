<?php

declare(strict_types=1);

namespace App\Services\BankStatement\Parsers;

class StatementRegex extends Regex
{
    private bool $isCredit = false;

    public function isCredit(): bool
    {
        return $this->isCredit;
    }

    public function setCredit(bool $credit): void
    {
        $this->isCredit = $credit;
    }
}
