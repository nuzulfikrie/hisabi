<?php

declare(strict_types=1);

namespace App\Services\BankStatement\Dtos;

class CimbStatementLine extends StatementLine
{
    private ?string $year = null;

    private ?string $month = null;

    private ?string $day = null;

    private ?string $balance = null;

    private ?string $expense = null;

    private ?string $expenseCategory = null;

    private ?string $expenseRemarks = null;

    private ?string $deposit = null;

    private ?string $depositCategory = null;

    private ?string $depositRemarks = null;

    public function getYear(): ?string
    {
        return $this->year;
    }

    public function setYear(?string $year): void
    {
        $this->year = $year;
    }

    public function getMonth(): ?string
    {
        return $this->month;
    }

    public function setMonth(?string $month): void
    {
        $this->month = $month;
    }

    public function getDay(): ?string
    {
        return $this->day;
    }

    public function setDay(?string $day): void
    {
        $this->day = $day;
    }

    public function getBalance(): ?string
    {
        return $this->balance;
    }

    public function setBalance(?string $balance): void
    {
        $this->balance = $balance;
    }

    public function getExpense(): ?string
    {
        return $this->expense;
    }

    public function setExpense(?string $expense): void
    {
        $this->expense = $expense;
    }

    public function getExpenseCategory(): ?string
    {
        return $this->expenseCategory;
    }

    public function setExpenseCategory(?string $expenseCategory): void
    {
        $this->expenseCategory = $expenseCategory;
    }

    public function getExpenseRemarks(): ?string
    {
        return $this->expenseRemarks;
    }

    public function setExpenseRemarks(?string $expenseRemarks): void
    {
        $this->expenseRemarks = $expenseRemarks;
    }

    public function getDeposit(): ?string
    {
        return $this->deposit;
    }

    public function setDeposit(?string $deposit): void
    {
        $this->deposit = $deposit;
    }

    public function getDepositCategory(): ?string
    {
        return $this->depositCategory;
    }

    public function setDepositCategory(?string $depositCategory): void
    {
        $this->depositCategory = $depositCategory;
    }

    public function getDepositRemarks(): ?string
    {
        return $this->depositRemarks;
    }

    public function setDepositRemarks(?string $depositRemarks): void
    {
        $this->depositRemarks = $depositRemarks;
    }

    /**
     * Get the full date in Y-m-d format.
     */
    public function getFullDate(): ?string
    {
        if ($this->year === null || $this->month === null || $this->day === null) {
            return null;
        }

        $monthNumber = $this->getMonthNumber();

        return sprintf('%s-%02d-%02d', $this->year, $monthNumber, (int) $this->day);
    }

    /**
     * Get month number from month name.
     */
    private function getMonthNumber(): int
    {
        $monthNames = [
            'jan' => 1, 'feb' => 2, 'mar' => 3, 'apr' => 4,
            'may' => 5, 'jun' => 6, 'jul' => 7, 'aug' => 8,
            'sept' => 9, 'oct' => 10, 'nov' => 11, 'dec' => 12,
        ];

        $monthLower = strtolower($this->month ?? '');

        return $monthNames[$monthLower] ?? 0;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'year' => $this->year,
            'month' => $this->month,
            'day' => $this->day,
            'balance' => $this->balance,
            'expense' => $this->expense,
            'expense_category' => $this->expenseCategory,
            'expense_remarks' => $this->expenseRemarks,
            'deposit' => $this->deposit,
            'deposit_category' => $this->depositCategory,
            'deposit_remarks' => $this->depositRemarks,
            'full_date' => $this->getFullDate(),
        ]);
    }
}
