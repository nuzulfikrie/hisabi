<?php

namespace App\Http\Controllers;

use App\Contracts\ReportManager;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    /**
     * Display the reports page.
     */
    public function index(Request $request): Response
    {
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $sections = app(ReportManager::class)->generate($startDate, $endDate);

        $range = $startDate && $endDate
            ? $startDate.' - '.$endDate
            : now()->format('F Y');

        return Inertia::render('Reports/Index', [
            'sections' => $sections,
            'currency' => config('hisabi.currency', 'MYR'),
            'range' => $range,
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
        ]);
    }
}
