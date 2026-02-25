<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Domains\Transaction\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SystemHealthController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => [
                'database' => $this->getDatabaseStatus(),
                'storage' => $this->getStorageStatus(),
                'queue' => $this->getQueueStatus(),
                'errors' => $this->getRecentErrors(),
                'users' => $this->getUserStats(),
                'transactions' => $this->getTransactionStats(),
                'system' => $this->getSystemInfo(),
            ],
        ]);
    }

    private function getDatabaseStatus(): array
    {
        try {
            DB::connection()->getPdo();
            return [
                'status' => 'connected',
                'message' => 'Database connection is healthy',
                'driver' => DB::connection()->getDriverName(),
                'database' => DB::connection()->getDatabaseName(),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Database connection failed: ' . $e->getMessage(),
                'driver' => config('database.default'),
                'database' => null,
            ];
        }
    }

    private function getStorageStatus(): array
    {
        try {
            $path = storage_path('app');
            
            $totalSpace = disk_total_space($path);
            $freeSpace = disk_free_space($path);
            $usedSpace = $totalSpace - $freeSpace;
            
            $usagePercentage = ($usedSpace / $totalSpace) * 100;
            
            return [
                'status' => $usagePercentage > 90 ? 'warning' : 'healthy',
                'total' => $this->formatBytes($totalSpace),
                'used' => $this->formatBytes($usedSpace),
                'free' => $this->formatBytes($freeSpace),
                'usage_percentage' => round($usagePercentage, 2),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Unable to retrieve storage information',
            ];
        }
    }

    private function getQueueStatus(): array
    {
        try {
            $connection = config('queue.default');
            $pending = 0;
            $failed = 0;

            if ($connection === 'database') {
                $pending = DB::table('jobs')->count();
                $failed = DB::table('failed_jobs')->count();
            } else {
                $failed = DB::table('failed_jobs')->count();
                $pending = 'N/A';
            }

            return [
                'status' => $failed > 0 ? 'warning' : 'healthy',
                'connection' => $connection,
                'pending_jobs' => $pending,
                'failed_jobs' => $failed,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Unable to retrieve queue information',
            ];
        }
    }

    private function getRecentErrors(): array
    {
        try {
            $logPath = storage_path('logs/laravel.log');
            
            if (!file_exists($logPath)) {
                return [
                    'count' => 0,
                    'errors' => [],
                ];
            }

            $content = file_get_contents($logPath);
            $lines = explode("\n", $content);
            $lines = array_reverse($lines);
            
            $errors = [];
            $errorCount = 0;
            
            foreach ($lines as $line) {
                if (str_contains($line, '.ERROR:') || str_contains($line, '.WARNING:')) {
                    $errors[] = $line;
                    $errorCount++;
                    if (count($errors) >= 10) {
                        break;
                    }
                }
            }

            return [
                'count' => $errorCount,
                'errors' => $errors,
            ];
        } catch (\Exception $e) {
            return [
                'count' => 0,
                'errors' => [],
                'message' => 'Unable to read log file',
            ];
        }
    }

    private function getUserStats(): array
    {
        $totalUsers = User::count();
        $activeToday = User::whereDate('updated_at', today())->count();

        return [
            'total' => $totalUsers,
            'active_today' => $activeToday,
        ];
    }

    private function getTransactionStats(): array
    {
        $totalTransactions = Transaction::count();
        $todayTransactions = Transaction::whereDate('created_at', today())->count();
        $totalAmount = Transaction::sum('amount');
        $todayAmount = Transaction::whereDate('created_at', today())->sum('amount');

        return [
            'total_count' => $totalTransactions,
            'today_count' => $todayTransactions,
            'total_amount' => round($totalAmount, 2),
            'today_amount' => round($todayAmount, 2),
        ];
    }

    private function getSystemInfo(): array
    {
        return [
            'php_version' => PHP_VERSION,
            'laravel_version' => \Illuminate\Foundation\Application::VERSION,
            'environment' => config('app.env'),
            'debug_mode' => config('app.debug'),
            'timezone' => config('app.timezone'),
            'cache_driver' => config('cache.default'),
            'session_driver' => config('session.driver'),
        ];
    }

    private function formatBytes($bytes, $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= 1024 ** $pow;
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
