<?php

declare(strict_types=1);

namespace App\Services\BankStatement\Dtos;

class StatementLine
{
    public function __construct(
        private ?string $postingDate = null,
        private ?string $transactionDate = null,
        private ?string $description = null,
        private ?string $amount = null,
        private ?string $countryCode = null,
        private bool $isCredit = false,
    ) {}

    public function getPostingDate(): ?string
    {
        return $this->postingDate;
    }

    public function setPostingDate(?string $postingDate): void
    {
        $this->postingDate = $postingDate;
    }

    public function getTransactionDate(): ?string
    {
        return $this->transactionDate;
    }

    public function setTransactionDate(?string $transactionDate): void
    {
        $this->transactionDate = $transactionDate;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getAmount(): ?string
    {
        return $this->amount;
    }

    public function setAmount(?string $amount): void
    {
        $this->amount = $amount;
    }

    public function getCountryCode(): ?string
    {
        return $this->countryCode;
    }

    public function setCountryCode(?string $countryCode): void
    {
        $this->countryCode = $countryCode;
    }

    public function isCredit(): bool
    {
        return $this->isCredit;
    }

    public function setCredit(bool $credit): void
    {
        $this->isCredit = $credit;
    }

    /**
     * Convert to array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'posting_date' => $this->postingDate,
            'transaction_date' => $this->transactionDate,
            'description' => $this->description,
            'amount' => $this->amount,
            'country_code' => $this->countryCode,
            'is_credit' => $this->isCredit,
        ];
    }
}
