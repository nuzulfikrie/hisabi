import axios from 'axios';

/**
 * Import API methods
 */

/**
 * Import transactions from CSV file
 * @param {File} file - CSV file to import
 * @returns {Promise}
 */
export function importCsv(file) {
    const formData = new FormData();
    formData.append('file', file);

    return axios.post('/api/v1/import/csv', formData, {
        headers: {
            'Content-Type': 'multipart/form-data',
        },
    });
}

/**
 * Import transactions from Excel file
 * @param {File} file - Excel file to import
 * @returns {Promise}
 */
export function importExcel(file) {
    const formData = new FormData();
    formData.append('file', file);

    return axios.post('/api/v1/import/excel', formData, {
        headers: {
            'Content-Type': 'multipart/form-data',
        },
    });
}

/**
 * Download import template
 * @param {string} format - 'csv' or 'excel'
 * @returns {Promise}
 */
export function downloadTemplate(format = 'csv') {
    return axios.get(`/import/template?format=${format}`, {
        responseType: 'blob',
    });
}
