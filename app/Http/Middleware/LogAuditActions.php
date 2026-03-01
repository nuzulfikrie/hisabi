<?php

namespace App\Http\Middleware;

use App\Services\AuditService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogAuditActions
{
    private array $actionsToLog = [
        'POST' => 'create',
        'PUT' => 'update',
        'PATCH' => 'update',
        'DELETE' => 'delete',
    ];

    private array $ignoredRoutes = [
        'login',
        'logout',
        'api/v1/admin/audit-logs',
    ];

    public function __construct(
        private readonly AuditService $auditService
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Capture the old values for updates (if applicable)
        $oldValues = null;
        $entityType = $this->getEntityType($request);
        
        if (in_array($request->method(), ['PUT', 'PATCH', 'DELETE'])) {
            // Try to find the model being modified
            $modelClass = $this->getModelClass($entityType);
            if ($modelClass) {
                $entityId = $request->route('id') ?? $request->route('uuid');
                if ($entityId) {
                    $model = $modelClass::find($entityId);
                    if ($model) {
                        $oldValues = $model->toArray();
                    }
                }
            }
        }

        $response = $next($request);

        // Log the action if it's a relevant method and successful
        if ($this->shouldLog($request, $response)) {
            $this->logAction($request, $response, $entityType, $oldValues);
        }

        return $response;
    }

    private function shouldLog(Request $request, Response $response): bool
    {
        // Only log successful requests
        if ($response->getStatusCode() >= 400) {
            return false;
        }

        // Check if method should be logged
        if (!isset($this->actionsToLog[$request->method()])) {
            return false;
        }

        // Check if route should be ignored
        foreach ($this->ignoredRoutes as $route) {
            if (str_contains($request->path(), $route)) {
                return false;
            }
        }

        return true;
    }

    private function logAction(Request $request, Response $response, string $entityType, ?array $oldValues): void
    {
        $action = $this->actionsToLog[$request->method()];
        $entityId = $request->route('id') ?? $request->route('uuid');
        $newValues = $request->all();

        // Filter out sensitive data
        $newValues = $this->filterSensitiveData($newValues);
        if ($oldValues) {
            $oldValues = $this->filterSensitiveData($oldValues);
        }

        $this->auditService->log(
            $action,
            $entityType,
            $entityId ? (string) $entityId : null,
            $oldValues,
            $newValues ?: null
        );
    }

    private function getEntityType(Request $request): string
    {
        $path = $request->path();
        
        // Extract entity type from URL path
        if (preg_match('/api\/v1\/(\w+)/', $path, $matches)) {
            return ucfirst(rtrim($matches[1], 's')); // Remove trailing 's' for singular
        }

        return 'Unknown';
    }

    private function getModelClass(string $entityType): ?string
    {
        $mapping = [
            'Transaction' => \App\Domains\Transaction\Models\Transaction::class,
            'Brand' => \App\Domains\Brand\Models\Brand::class,
            'Category' => \App\Models\Category::class,
            'Budget' => \App\Domains\Budget\Models\Budget::class,
            'Sms' => \App\Domains\Sms\Models\Sms::class,
            'Tag' => \App\Domains\Tag\Models\Tag::class,
            'User' => \App\Models\User::class,
        ];

        return $mapping[$entityType] ?? null;
    }

    private function filterSensitiveData(array $data): array
    {
        $sensitiveFields = ['password', 'password_confirmation', 'token', 'secret'];
        
        foreach ($sensitiveFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = '***REDACTED***';
            }
        }

        return $data;
    }
}
