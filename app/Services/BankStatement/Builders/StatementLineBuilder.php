<?php

declare(strict_types=1);

namespace App\Services\BankStatement\Builders;

use App\Services\BankStatement\Dtos\StatementLine;

class StatementLineBuilder
{
    private ?string $postingDate = null;

    private ?string $transactionDate = null;

    private ?string $description = null;

    private ?string $amount = null;

    private ?string $countryCode = null;

    private bool $isCredit = false;

    public function withPostingDate(?string $postingDate): self
    {
        $this->postingDate = $postingDate;

        return $this;
    }

    public function withTransactionDate(?string $transactionDate): self
    {
        $this->transactionDate = $transactionDate;

        return $this;
    }

    public function withDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function withAmount(?string $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    public function withCountryCode(?string $countryCode): self
    {
        $this->countryCode = $countryCode;

        return $this;
    }

    public function withCredit(bool $isCredit): self
    {
        $this->isCredit = $isCredit;

        return $this;
    }

    public function build(): StatementLine
    {
        return new StatementLine(
            $this->postingDate,
            $this->transactionDate,
            $this->description,
            $this->amount,
            $this->countryCode,
            $this->isCredit,
        );
    }

    public function reset(): self
    {
        $this->postingDate = null;
        $this->transactionDate = null;
        $this->description = null;
        $this->amount = null;
        $this->countryCode = null;
        $this->isCredit = false;

        return $this;
    }
}
