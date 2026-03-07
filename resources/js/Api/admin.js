import { getCsrfToken } from './common.js';

export const getAuditLogs = async (page = 1, perPage = 50, filters = {}) => {
    const params = new URLSearchParams({
        page: page.toString(),
        per_page: perPage.toString(),
    });

    if (filters.userId) {
        params.append('user_id', filters.userId);
    }
    if (filters.action) {
        params.append('action', filters.action);
    }
    if (filters.entityType) {
        params.append('entity_type', filters.entityType);
    }
    if (filters.entityId) {
        params.append('entity_id', filters.entityId);
    }
    if (filters.dateFrom) {
        params.append('date_from', filters.dateFrom);
    }
    if (filters.dateTo) {
        params.append('date_to', filters.dateTo);
    }

    const response = await fetch(`/api/v1/admin/audit-logs?${params.toString()}`, {
        method: 'GET',
    });

    if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
    }

    return await response.json();
};

export const getAuditLog = async (id) => {
    const response = await fetch(`/api/v1/admin/audit-logs/${id}`, {
        method: 'GET',
    });

    if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
    }

    return await response.json();
};

export const getAuditLogActions = async () => {
    const response = await fetch('/api/v1/admin/audit-logs/actions', {
        method: 'GET',
    });

    if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
    }

    const data = await response.json();
    return data.data || [];
};

export const getAuditLogEntityTypes = async () => {
    const response = await fetch('/api/v1/admin/audit-logs/entity-types', {
        method: 'GET',
    });

    if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
    }

    const data = await response.json();
    return data.data || [];
};

export const getSystemHealth = async () => {
    const response = await fetch('/api/v1/admin/system-health', {
        method: 'GET',
    });

    if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
    }

    return await response.json();
};
