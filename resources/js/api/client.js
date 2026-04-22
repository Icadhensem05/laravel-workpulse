import axios from 'axios';

let pendingRequests = 0;
let slowNetworkTimer = null;
let slowNetworkActive = false;

function getBaseUrl() {
    const configured = document.documentElement.dataset.legacyApiBaseUrl || '';
    return configured.replace(/\/+$/, '');
}

function getLocalBaseUrl() {
    const configured = document.documentElement.dataset.localApiBaseUrl || '';
    return configured.replace(/\/+$/, '');
}

function mapApiError(error) {
    const status = error?.response?.status;
    const payload = error?.response?.data;

    if (Array.isArray(payload?.errors) && payload.errors.length) {
        return payload.errors.join(' ');
    }

    if (payload?.errors && typeof payload.errors === 'object') {
        const firstMessage = Object.values(payload.errors).flat().find(Boolean);
        if (firstMessage) {
            return String(firstMessage);
        }
    }

    if (payload?.message) {
        return String(payload.message);
    }

    if (payload?.error) {
        return String(payload.error);
    }

    if (status === 401) {
        return 'Your session has expired. Please sign in again.';
    }

    if (status === 403) {
        return 'You do not have permission to perform this action.';
    }

    if (status === 404) {
        return 'The requested record could not be found.';
    }

    if (status === 422) {
        return 'Please review the form and correct the highlighted values.';
    }

    if (status >= 500) {
        return 'The server could not complete the request. Please try again shortly.';
    }

    if (error?.code === 'ERR_NETWORK') {
        return 'Unable to reach the server. Check your connection and try again.';
    }

    if (error?.message) {
        return error.message;
    }

    return 'Request failed';
}

function normalizeValidationErrors(payload) {
    if (Array.isArray(payload?.errors)) {
        return payload.errors.filter(Boolean).map((value) => String(value));
    }

    if (payload?.errors && typeof payload.errors === 'object') {
        return Object.entries(payload.errors).reduce((collection, [field, value]) => {
            const messages = Array.isArray(value) ? value : [value];
            messages.filter(Boolean).forEach((message) => {
                collection.push({
                    field,
                    message: String(message),
                });
            });
            return collection;
        }, []);
    }

    return [];
}

function normalizePaginationMeta(payload) {
    const meta = payload?.meta || payload?.pagination || {};
    const page = Number(meta.page ?? meta.current_page ?? 1);
    const perPage = Number(meta.per_page ?? meta.page_size ?? meta.limit ?? 0);
    const total = Number(meta.total ?? meta.count ?? 0);

    return {
        page: Number.isFinite(page) ? page : 1,
        per_page: Number.isFinite(perPage) ? perPage : 0,
        total: Number.isFinite(total) ? total : 0,
    };
}

function normalizeReadPayload(payload) {
    if (payload == null) {
        return {
            success: true,
            message: '',
            errors: [],
            meta: normalizePaginationMeta({}),
            data: null,
            raw: payload,
        };
    }

    const success = typeof payload?.success === 'boolean'
        ? payload.success
        : !payload?.error;

    const data = Object.prototype.hasOwnProperty.call(payload, 'data')
        ? payload.data
        : payload;

    return {
        success,
        message: String(payload?.message || payload?.error || ''),
        errors: normalizeValidationErrors(payload),
        meta: normalizePaginationMeta(payload),
        data,
        raw: payload,
    };
}

function normalizeMutationPayload(payload) {
    const raw = payload && typeof payload === 'object' ? payload : {};
    const success = typeof raw.success === 'boolean'
        ? raw.success
        : !raw.error;

    const errors = normalizeValidationErrors(raw);
    const message = String(raw.message || raw.error || (success ? 'Request completed.' : 'Request failed.'));

    let data = null;

    if (Object.prototype.hasOwnProperty.call(raw, 'data')) {
        data = raw.data;
    } else {
        const remainder = { ...raw };
        delete remainder.success;
        delete remainder.message;
        delete remainder.error;
        delete remainder.errors;
        delete remainder.meta;
        delete remainder.pagination;
        delete remainder.status;

        data = Object.keys(remainder).length ? remainder : null;
    }

    return {
        success,
        message,
        errors,
        meta: normalizePaginationMeta(raw),
        data,
        raw,
    };
}

function shouldRetryRequest(error) {
    const method = String(error?.config?.method || 'get').toLowerCase();
    const status = Number(error?.response?.status || 0);

    if (method !== 'get') {
        return false;
    }

    if (error?.code === 'ERR_NETWORK' || error?.code === 'ECONNABORTED') {
        return true;
    }

    return status >= 500;
}

function delay(ms) {
    return new Promise((resolve) => window.setTimeout(resolve, ms));
}

function scheduleSlowNetworkSignal() {
    if (slowNetworkTimer || slowNetworkActive) {
        return;
    }

    slowNetworkTimer = window.setTimeout(() => {
        if (pendingRequests > 0) {
            slowNetworkActive = true;
            window.dispatchEvent(new CustomEvent('workpulse:slow-network'));
        }
        slowNetworkTimer = null;
    }, 1200);
}

function clearSlowNetworkSignal() {
    if (pendingRequests <= 0) {
        pendingRequests = 0;

        if (slowNetworkTimer) {
            window.clearTimeout(slowNetworkTimer);
            slowNetworkTimer = null;
        }

        if (slowNetworkActive) {
            slowNetworkActive = false;
            window.dispatchEvent(new CustomEvent('workpulse:network-restored'));
        }
    }
}

const api = axios.create({
    baseURL: getBaseUrl(),
    withCredentials: true,
    headers: {
        'X-Requested-With': 'XMLHttpRequest',
        Accept: 'application/json',
    },
});

const localApi = axios.create({
    baseURL: getLocalBaseUrl(),
    withCredentials: true,
    headers: {
        'X-Requested-With': 'XMLHttpRequest',
        Accept: 'application/json',
    },
});

api.interceptors.request.use((config) => {
    pendingRequests += 1;
    scheduleSlowNetworkSignal();
    return config;
});

localApi.interceptors.request.use((config) => {
    pendingRequests += 1;
    scheduleSlowNetworkSignal();
    return config;
});

api.interceptors.response.use(
    (response) => {
        pendingRequests -= 1;
        clearSlowNetworkSignal();
        return response;
    },
    (error) => {
        pendingRequests -= 1;
        clearSlowNetworkSignal();

        if (error?.response?.status === 401) {
            window.dispatchEvent(new CustomEvent('workpulse:unauthorized', {
                detail: {
                    message: mapApiError(error),
                },
            }));
        }

        return Promise.reject(error);
    }
);

localApi.interceptors.response.use(
    (response) => {
        pendingRequests -= 1;
        clearSlowNetworkSignal();
        return response;
    },
    (error) => {
        pendingRequests -= 1;
        clearSlowNetworkSignal();

        if (error?.response?.status === 401) {
            window.dispatchEvent(new CustomEvent('workpulse:unauthorized', {
                detail: {
                    message: mapApiError(error),
                },
            }));
        }

        return Promise.reject(error);
    }
);

async function getWithRetry(url, config = {}, options = {}) {
    const retries = Number(options.retries ?? 1);
    const retryDelay = Number(options.retryDelay ?? 500);

    let lastError;

    for (let attempt = 0; attempt <= retries; attempt += 1) {
        try {
            const response = await api.get(url, config);
            return response.data;
        } catch (error) {
            lastError = error;

            if (attempt >= retries || !shouldRetryRequest(error)) {
                throw error;
            }

            await delay(retryDelay * (attempt + 1));
        }
    }

    throw lastError;
}

async function getNormalized(url, config = {}, options = {}) {
    const payload = await getWithRetry(url, config, options);
    return normalizeReadPayload(payload);
}

async function postNormalized(url, payload = {}, config = {}) {
    const response = await api.post(url, payload, config);
    return normalizeMutationPayload(response.data);
}

async function postMultipartNormalized(url, formData, config = {}) {
    const response = await api.post(url, formData, config);
    return normalizeMutationPayload(response.data);
}

async function getLocalNormalized(url, config = {}, options = {}) {
    const retries = Number(options.retries ?? 1);
    const retryDelay = Number(options.retryDelay ?? 500);

    let lastError;

    for (let attempt = 0; attempt <= retries; attempt += 1) {
        try {
            const response = await localApi.get(url, config);
            return normalizeReadPayload(response.data);
        } catch (error) {
            lastError = error;

            if (attempt >= retries || !shouldRetryRequest(error)) {
                throw error;
            }

            await delay(retryDelay * (attempt + 1));
        }
    }

    throw lastError;
}

async function postLocalNormalized(url, payload = {}, config = {}) {
    const response = await localApi.post(url, payload, config);
    return normalizeMutationPayload(response.data);
}

async function putLocalNormalized(url, payload = {}, config = {}) {
    const response = await localApi.put(url, payload, config);
    return normalizeMutationPayload(response.data);
}

async function deleteLocalNormalized(url, config = {}) {
    const response = await localApi.delete(url, config);
    return normalizeMutationPayload(response.data);
}

async function postLocalMultipartNormalized(url, formData, config = {}) {
    const response = await localApi.post(url, formData, config);
    return normalizeMutationPayload(response.data);
}

export {
    api,
    deleteLocalNormalized,
    localApi,
    getNormalized,
    getLocalNormalized,
    getWithRetry,
    mapApiError,
    normalizeMutationPayload,
    normalizePaginationMeta,
    normalizeReadPayload,
    normalizeValidationErrors,
    postMultipartNormalized,
    postLocalMultipartNormalized,
    postNormalized,
    postLocalNormalized,
    putLocalNormalized,
};
