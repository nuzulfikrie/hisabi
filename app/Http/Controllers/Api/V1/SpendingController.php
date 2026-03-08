<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Domains\Transaction\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SpendingController extends Controller
{
    public function summary(Request $request)
    {
        $type = $request->get('type'); // 'home', 'personal', or null for all
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->get('end_date', Carbon::now()->endOfMonth()->toDateString());

        $query = Transaction::query()
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($type) {
            $query->byType($type);
        }

        $total = $query->sum('amount');

        return response()->json([
            'total' => $total,
            'formatted_total' => number_format($total, 2),
            'type' => $type ?? 'all',
            'date_range' => [
                'start' => $startDate,
                'end' => $endDate,
            ],
        ]);
    }

    public function byCategory(Request $request)
    {
        $type = $request->get('type');
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->get('end_date', Carbon::now()->endOfMonth()->toDateString());

        $query = Transaction::query()
            ->select(
                'categories.name as category_name',
                'categories.color as category_color',
                DB::raw('SUM(transactions.amount) as total_amount'),
                DB::raw('COUNT(transactions.id) as transaction_count')
            )
            ->join('brands', 'transactions.brand_id', '=', 'brands.id')
            ->join('categories', 'brands.category_id', '=', 'categories.id')
            ->whereBetween('transactions.created_at', [$startDate, $endDate])
            ->groupBy('categories.id', 'categories.name', 'categories.color')
            ->orderByDesc('total_amount');

        if ($type) {
            $query->where('transactions.type', $type);
        }

        $categories = $query->get();

        return response()->json([
            'categories' => $categories,
            'date_range' => [
                'start' => $startDate,
                'end' => $endDate,
            ],
        ]);
    }

    public function byType(Request $request)
    {
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->get('end_date', Carbon::now()->endOfMonth()->toDateString());

        $typeData = Transaction::query()
            ->select(
                'type',
                DB::raw('SUM(amount) as total_amount'),
                DB::raw('COUNT(id) as transaction_count')
            )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('type')
            ->groupBy('type')
            ->get();

        $total = $typeData->sum('total_amount');

        $percentages = $typeData->map(function ($item) use ($total) {
            return [
                'type' => $item->type,
                'total' => $item->total_amount,
                'count' => $item->transaction_count,
                'percentage' => $total > 0 ? round(($item->total_amount / $total) * 100) : 0,
            ];
        });

        return response()->json([
            'data' => $percentages,
            'total' => $total,
            'date_range' => [
                'start' => $startDate,
                'end' => $endDate,
            ],
        ]);
    }

    public function transactions(Request $request)
    {
        $type = $request->get('type');
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->get('end_date', Carbon::now()->endOfMonth()->toDateString());
        $perPage = $request->get('per_page', 10);

        $query = Transaction::query()
            ->with(['brand.category'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderByDesc('created_at');

        if ($type) {
            $query->byType($type);
        }

        $transactions = $query->paginate($perPage);

        return response()->json([
            'transactions' => $transactions,
            'date_range' => [
                'start' => $startDate,
                'end' => $endDate,
            ],
        ]);
    }
}
