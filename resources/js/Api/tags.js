import { getCsrfToken } from './common.js';

export const getAllTags = async () => {
    const response = await fetch('/api/v1/tags/all', {
        method: 'GET',
    });

    if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
    }

    const result = await response.json();

    return {
        data: {
            allTags: result.data
        }
    };
}

export const getTags = async (page, searchQuery) => {
    const params = new URLSearchParams({
        page: page.toString(),
        perPage: '50'
    });

    if (searchQuery) {
        params.append('filter[search]', searchQuery);
    }

    const response = await fetch(`/api/v1/tags?${params.toString()}`, {
        method: 'GET',
    });

    if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
    }

    const data = await response.json();

    return {
        data: {
            tags: data
        }
    };
}

export const createTag = async ({name, color}) => {
    const response = await fetch('/api/v1/tags', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': getCsrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
        body: JSON.stringify({
            name: name,
            color: color
        })
    });

    if (!response.ok) {
        const errorData = await response.json();
        throw new Error(errorData.message || `HTTP error! status: ${response.status}`);
    }

    const result = await response.json();

    return {
        data: {
            createTag: result.tag
        }
    };
}

export const updateTag = async ({uuid, name, color}) => {
    const response = await fetch(`/api/v1/tags/${uuid}`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': getCsrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
        body: JSON.stringify({
            name: name,
            color: color
        })
    });

    if (!response.ok) {
        const errorData = await response.json();
        throw new Error(errorData.message || `HTTP error! status: ${response.status}`);
    }

    const result = await response.json();

    return {
        data: {
            updateTag: result.tag
        }
    };
}

export const deleteTag = async (uuid) => {
    const response = await fetch(`/api/v1/tags/${uuid}`, {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': getCsrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
    });

    if (!response.ok) {
        const errorData = await response.json();
        throw new Error(errorData.message || `HTTP error! status: ${response.status}`);
    }

    const result = await response.json();

    return {
        data: {
            deleteTag: result.tag
        }
    };
}

export const getTaggedTransactions = async (uuid, page = 1) => {
    const params = new URLSearchParams({
        page: page.toString(),
        perPage: '50'
    });

    const response = await fetch(`/api/v1/tags/${uuid}/transactions?${params.toString()}`, {
        method: 'GET',
    });

    if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
    }

    const data = await response.json();

    return {
        data: {
            transactions: data
        }
    };
}
