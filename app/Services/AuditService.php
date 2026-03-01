<?php

namespace App\Services;

use App\Domains\Audit\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditService
{
    /**
     * Log an action to the audit log.
     *
     * @param string $action
     * @param string $entityType
     * @param string|null $entityId
     * @param array|null $oldValues
     * @param array|null $newValues
     * @param int|null $userId
     * @return AuditLog
     */
    public function log(
        string $action,
        string $entityType,
        ?string $entityId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?int $userId = null
    ): AuditLog {
        return AuditLog::create([
            'user_id' => $userId ?? Auth::id(),
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }

    /**
     * Log a create action.
     *
     * @param Model $model
     * @param int|null $userId
     * @return AuditLog
     */
    public function logCreate(Model $model, ?int $userId = null): AuditLog
    {
        return $this->log(
            'create',
            class_basename($model),
            (string) $model->getKey(),
            null,
            $model->toArray(),
            $userId
        );
    }

    /**
     * Log an update action.
     *
     * @param Model $model
     * @param array $oldValues
     * @param int|null $userId
     * @return AuditLog
     */
    public function logUpdate(Model $model, array $oldValues, ?int $userId = null): AuditLog
    {
        return $this->log(
            'update',
            class_basename($model),
            (string) $model->getKey(),
            $oldValues,
            $model->toArray(),
            $userId
        );
    }

    /**
     * Log a delete action.
     *
     * @param Model $model
     * @param int|null $userId
     * @return AuditLog
     */
    public function logDelete(Model $model, ?int $userId = null): AuditLog
    {
        return $this->log(
            'delete',
            class_basename($model),
            (string) $model->getKey(),
            $model->toArray(),
            null,
            $userId
        );
    }

    /**
     * Log a login action.
     *
     * @param int|null $userId
     * @return AuditLog
     */
    public function logLogin(?int $userId = null): AuditLog
    {
        return $this->log('login', 'User', (string) $userId, null, null, $userId);
    }

    /**
     * Log a logout action.
     *
     * @param int|null $userId
     * @return AuditLog
     */
    public function logLogout(?int $userId = null): AuditLog
    {
        return $this->log('logout', 'User', (string) $userId, null, null, $userId);
    }

    /**
     * Get recent audit logs with pagination.
     *
     * @param int $perPage
     * @param array $filters
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getPaginated(int $perPage = 50, array $filters = [])
    {
        $query = AuditLog::query()
            ->with('user')
            ->orderBy('created_at', 'desc');

        if (isset($filters['user_id'])) {
            $query->byUser($filters['user_id']);
        }

        if (isset($filters['action'])) {
            $query->byAction($filters['action']);
        }

        if (isset($filters['entity_type'])) {
            $query->byEntity($filters['entity_type'], $filters['entity_id'] ?? null);
        }

        if (isset($filters['date_from']) && isset($filters['date_to'])) {
            $query->inDateRange($filters['date_from'], $filters['date_to']);
        }

        return $query->paginate($perPage);
    }
}
