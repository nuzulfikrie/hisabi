<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class ReportsExport implements FromView
{
    protected array $sections;
    protected string $currency;
    protected string $range;

    public function __construct(array $sections, string $currency, string $range)
    {
        $this->sections = $sections;
        $this->currency = $currency;
        $this->range = $range;
    }

    public function view(): View
    {
        return view('exports.report', [
            'sections' => $this->sections,
            'currency' => $this->currency,
            'range' => $this->range,
        ]);
    }
}
