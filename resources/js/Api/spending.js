import axios from 'axios';

export const getSpendingSummary = (type = null, startDate = null, endDate = null) => {
    const params = {};
    if (type && type !== 'all') params.type = type;
    if (startDate) params.start_date = startDate;
    if (endDate) params.end_date = endDate;
    
    return axios.get('/api/v1/spending/summary', { params });
};

export const getSpendingByCategory = (type = null, startDate = null, endDate = null) => {
    const params = {};
    if (type && type !== 'all') params.type = type;
    if (startDate) params.start_date = startDate;
    if (endDate) params.end_date = endDate;
    
    return axios.get('/api/v1/spending/by-category', { params });
};

export const getSpendingByType = (startDate = null, endDate = null) => {
    const params = {};
    if (startDate) params.start_date = startDate;
    if (endDate) params.end_date = endDate;
    
    return axios.get('/api/v1/spending/by-type', { params });
};

export const getSpendingTransactions = (type = null, startDate = null, endDate = null, page = 1, perPage = 10) => {
    const params = { page, per_page: perPage };
    if (type && type !== 'all') params.type = type;
    if (startDate) params.start_date = startDate;
    if (endDate) params.end_date = endDate;
    
    return axios.get('/api/v1/spending/transactions', { params });
};
