<?php

declare(strict_types=1);

namespace App\Services\BankStatement;

use App\Services\BankStatement\Contracts\BankStatementParser;
use App\Services\BankStatement\Dtos\ParsedStatement;
use App\Services\BankStatement\Resolvers\BankStatementResolver;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class BankStatementService
{
    protected BankStatementResolver $resolver;

    protected Filesystem $filesystem;

    public function __construct(
        ?BankStatementResolver $resolver = null,
        ?Filesystem $filesystem = null,
    ) {
        $this->resolver = $resolver ?? new BankStatementResolver;
        $this->filesystem = $filesystem ?? new Filesystem;
    }

    /**
     * Parse a single file and return the parsed statement.
     *
     * @throws InvalidArgumentException
     */
    public function parseFile(string $filePath, ?string $bankIdentifier = null): ParsedStatement
    {
        if (! file_exists($filePath)) {
            throw new InvalidArgumentException("File not found: {$filePath}");
        }

        $parser = $bankIdentifier !== null
            ? $this->resolver->resolveByIdentifier($bankIdentifier)
            : $this->resolver->resolve($filePath);

        return $parser->parseFile($filePath);
    }

    /**
     * Parse all PDF files in a directory.
     *
     * @return Collection<int, ParsedStatement>
     */
    public function parseDirectory(string $directoryPath, ?string $bankIdentifier = null): Collection
    {
        if (! is_dir($directoryPath)) {
            throw new InvalidArgumentException("Directory not found: {$directoryPath}");
        }

        $parser = $bankIdentifier !== null
            ? $this->resolver->resolveByIdentifier($bankIdentifier)
            : null;

        $results = new Collection;
        $files = $this->filesystem->files($directoryPath);

        foreach ($files as $file) {
            if ($file->getExtension() !== 'pdf') {
                continue;
            }

            try {
                if ($parser !== null) {
                    $result = $parser->parseFile($file->getRealPath());
                } else {
                    $result = $this->parseFile($file->getRealPath());
                }
                $results->push($result);
            } catch (\Exception $e) {
                Log::error("Failed to parse file {$file->getFilename()}: ".$e->getMessage());
            }
        }

        return $results;
    }

    /**
     * Export parsed statement to Excel format.
     *
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     * @throws InvalidArgumentException
     */
    public function exportToExcel(
        ParsedStatement $statement,
        string $outputPath,
        bool $transactionsOnly = false,
    ): string {
        if ($transactionsOnly) {
            return $this->exportTransactionsOnly($statement, $outputPath);
        }

        return $this->exportFullStatement($statement, $outputPath);
    }

    /**
     * Export only transactions (no summary) to Excel.
     *
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     * @throws InvalidArgumentException
     */
    protected function exportTransactionsOnly(ParsedStatement $statement, string $outputPath): string
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        $rowNum = 0;

        foreach ($statement->getAllStatements() as $card => $lines) {
            foreach ($lines as $line) {
                $sheet->setCellValue('A'.($rowNum + 1), $line->getPostingDate());
                $sheet->setCellValue('B'.($rowNum + 1), $line->getTransactionDate());
                $sheet->setCellValue('C'.($rowNum + 1), $line->getDescription());

                $amount = $line->getAmount() ?? '';
                if (! $line->isCredit()) {
                    $amount = '-'.$amount;
                }
                $sheet->setCellValue('D'.($rowNum + 1), $amount);

                $rowNum++;
            }
        }

        return $this->saveSpreadsheet($spreadsheet, $outputPath, $statement);
    }

    /**
     * Export full statement with summary to Excel.
     *
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     * @throws InvalidArgumentException
     */
    protected function exportFullStatement(ParsedStatement $statement, string $outputPath): string
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        $rowNum = 0;

        // Statement month
        $sheet->setCellValue('A'.($rowNum + 1), 'Statement Month');
        $sheet->setCellValue('B'.($rowNum + 1), $statement->getPaddedMonth().'/'.$statement->getStatementYear());
        $rowNum += 2;

        // Balance information
        $sheet->setCellValue('A'.($rowNum + 1), 'Current Balance');
        $sheet->setCellValue('B'.($rowNum + 1), $statement->getMainValue('main_current_account_balance'));
        $rowNum++;

        $sheet->setCellValue('A'.($rowNum + 1), 'Previous Balance');
        $sheet->setCellValue('B'.($rowNum + 1), $statement->getMainValue('main_previous_balance'));
        $rowNum++;

        $sheet->setCellValue('A'.($rowNum + 1), 'Minimum Payment');
        $sheet->setCellValue('B'.($rowNum + 1), $statement->getMainValue('main_minimum_amount_to_pay'));
        $rowNum += 3;

        foreach ($statement->getAllStatements() as $card => $lines) {
            // Card number header
            $sheet->setCellValue('A'.($rowNum + 1), 'Credit Card Number');
            $sheet->setCellValue('B'.($rowNum + 1), $card);
            $rowNum += 2;

            // Separate credits and debits
            $credits = $lines->filter(fn ($line) => $line->isCredit());
            $debits = $lines->filter(fn ($line) => ! $line->isCredit());

            // Credits section
            if ($credits->isNotEmpty()) {
                $sheet->setCellValue('B'.($rowNum + 1), 'CREDIT');
                $rowNum++;

                foreach ($credits as $line) {
                    $sheet->setCellValue('A'.($rowNum + 1), $line->getPostingDate());
                    $sheet->setCellValue('B'.($rowNum + 1), $line->getTransactionDate());
                    $sheet->setCellValue('C'.($rowNum + 1), $line->getDescription());
                    $sheet->setCellValue('D'.($rowNum + 1), $line->getAmount());
                    $rowNum++;
                }

                $sheet->setCellValue('C'.($rowNum + 1), 'TOTAL CREDIT');
                $sheet->setCellValue('D'.($rowNum + 1), $statement->getSpecificValue($card, 'total_credit'));
                $rowNum += 2;
            }

            // Debits section
            if ($debits->isNotEmpty()) {
                $sheet->setCellValue('B'.($rowNum + 1), 'DEBIT');
                $rowNum++;

                foreach ($debits as $line) {
                    $sheet->setCellValue('A'.($rowNum + 1), $line->getPostingDate());
                    $sheet->setCellValue('B'.($rowNum + 1), $line->getTransactionDate());
                    $sheet->setCellValue('C'.($rowNum + 1), $line->getDescription());
                    $sheet->setCellValue('D'.($rowNum + 1), $line->getAmount());
                    $rowNum++;
                }

                $sheet->setCellValue('C'.($rowNum + 1), 'TOTAL DEBIT');
                $sheet->setCellValue('D'.($rowNum + 1), $statement->getSpecificValue($card, 'total_debit'));
                $rowNum += 2;
            }
        }

        return $this->saveSpreadsheet($spreadsheet, $outputPath, $statement);
    }

    /**
     * Save spreadsheet to file.
     *
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     * @throws InvalidArgumentException
     */
    protected function saveSpreadsheet(
        Spreadsheet $spreadsheet,
        string $outputPath,
        ParsedStatement $statement,
    ): string {
        $filename = $statement->getStatementYear().'_'.$statement->getPaddedMonth().'.xlsx';
        $fullPath = rtrim($outputPath, '/').'/'.$filename;

        $dir = dirname($fullPath);
        if (! is_dir($dir) && ! mkdir($dir, 0755, true)) {
            throw new InvalidArgumentException("Failed to create output directory: {$dir}");
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($fullPath);
        $spreadsheet->disconnectWorksheets();

        return $fullPath;
    }

    /**
     * Get the resolver instance.
     */
    public function getResolver(): BankStatementResolver
    {
        return $this->resolver;
    }

    /**
     * Get available bank parsers.
     *
     * @return array<int, array{identifier: string, name: string}>
     */
    public function getAvailableBanks(): array
    {
        return $this->resolver->getAvailableBanks();
    }

    /**
     * Check if a parser exists for the given file.
     */
    public function canParse(string $filePath): bool
    {
        return $this->resolver->canParse($filePath);
    }

    /**
     * Register a custom parser.
     */
    public function registerParser(BankStatementParser $parser): self
    {
        $this->resolver->registerParser($parser);

        return $this;
    }
}
