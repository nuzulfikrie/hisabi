import { getCsrfToken } from './common.js';

export const getSmsParserRules = async () => {
    const response = await fetch('/api/v1/sms-parser-rules', {
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
            rules: result.rules
        }
    };
}

export const createSmsParserRule = async (data) => {
    const response = await fetch('/api/v1/sms-parser-rules', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': getCsrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
        body: JSON.stringify(data)
    });

    if (!response.ok) {
        const errorData = await response.json().catch(() => ({}));
        throw new Error(errorData.message || `HTTP error! status: ${response.status}`);
    }

    const result = await response.json();

    return {
        data: {
            rule: result.rule
        }
    };
}

export const updateSmsParserRule = async (uuid, data) => {
    const response = await fetch(`/api/v1/sms-parser-rules/${uuid}`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': getCsrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
        body: JSON.stringify(data)
    });

    if (!response.ok) {
        const errorData = await response.json().catch(() => ({}));
        throw new Error(errorData.message || `HTTP error! status: ${response.status}`);
    }

    const result = await response.json();

    return {
        data: {
            rule: result.rule
        }
    };
}

export const deleteSmsParserRule = async (uuid) => {
    const response = await fetch(`/api/v1/sms-parser-rules/${uuid}`, {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': getCsrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
    });

    if (!response.ok) {
        const errorData = await response.json().catch(() => ({}));
        throw new Error(errorData.message || `HTTP error! status: ${response.status}`);
    }

    const result = await response.json();

    return {
        data: {
            rule: result.rule
        }
    };
}

export const testSmsParserRule = async ({ sms, pattern }) => {
    const body = { sms };
    if (pattern) body.pattern = pattern;

    const response = await fetch('/api/v1/sms-parser-rules/test', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': getCsrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
        body: JSON.stringify(body)
    });

    if (!response.ok) {
        const errorData = await response.json().catch(() => ({}));
        throw new Error(errorData.message || `HTTP error! status: ${response.status}`);
    }

    return await response.json();
}
