import { getCsrfToken } from './common.js';

export const getUserPreferences = async () => {
    const response = await fetch('/api/v1/user/preferences', {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
        },
    });

    if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
    }

    const result = await response.json();

    return {
        data: {
            preferences: result.preferences
        }
    };
}

export const updateUserPreferences = async (preferences) => {
    const response = await fetch('/api/v1/user/preferences', {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': getCsrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
        body: JSON.stringify(preferences)
    });

    if (!response.ok) {
        const errorData = await response.json().catch(() => ({}));
        throw new Error(errorData.message || `HTTP error! status: ${response.status}`);
    }

    const result = await response.json();

    return {
        data: {
            preferences: result.preferences
        }
    };
}
