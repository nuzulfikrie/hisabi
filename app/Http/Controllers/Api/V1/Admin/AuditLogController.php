<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuditLogController extends Controller
{
    public function __construct(
        private readonly AuditService $auditService
    ) {}

    public function index(Request $request): JsonResponse
    {
        // Check if user is admin
        if (!$this->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $filters = [];

        if ($request->has('user_id')) {
            $filters['user_id'] = $request->input('user_id');
        }

        if ($request->has('action')) {
            $filters['action'] = $request->input('action');
        }

        if ($request->has('entity_type')) {
            $filters['entity_type'] = $request->input('entity_type');
        }

        if ($request->has('entity_id')) {
            $filters['entity_id'] = $request->input('entity_id');
        }

        if ($request->has('date_from')) {
            $filters['date_from'] = $request->input('date_from');
        }

        if ($request->has('date_to')) {
            $filters['date_to'] = $request->input('date_to');
        }

        $perPage = $request->input('per_page', 50);
        
        $logs = $this->auditService->getPaginated($perPage, $filters);

        return response()->json([
            'data' => $logs->items(),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ],
        ]);
    }

    public function show(string $id): JsonResponse
    {
        // Check if user is admin
        if (!$this->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $log = \App\Domains\Audit\Models\AuditLog::with('user')->findOrFail($id);

        return response()->json([
            'data' => $log,
            'diff' => $log->diff,
        ]);
    }

    public function actions(): JsonResponse
    {
        // Check if user is admin
        if (!$this->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $actions = \App\Domains\Audit\Models\AuditLog::select('action')
            ->distinct()
            ->pluck('action');

        return response()->json([
            'data' => $actions,
        ]);
    }

    public function entityTypes(): JsonResponse
    {
        // Check if user is admin
        if (!$this->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $entityTypes = \App\Domains\Audit\Models\AuditLog::select('entity_type')
            ->distinct()
            ->pluck('entity_type');

        return response()->json([
            'data' => $entityTypes,
        ]);
    }

    private function isAdmin(): bool
    {
        $user = Auth::user();
        
        if (!$user) {
            return false;
        }

        // Check for is_admin attribute or role
        return $user->is_admin ?? false;
    }
}
