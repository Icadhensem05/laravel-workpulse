import { getLocalNormalized, postLocalNormalized } from './client';

export async function fetchAdminUsers() {
    const result = await getLocalNormalized('/admin/users');
    const data = result.data && typeof result.data === 'object' ? result.data : {};

    return {
        users: Array.isArray(data.rows) ? data.rows : [],
    };
}

export async function fetchAdminApprovals() {
    const result = await getLocalNormalized('/admin/approvals');
    const data = result.data && typeof result.data === 'object' ? result.data : {};

    return {
        rows: Array.isArray(data.rows) ? data.rows : [],
    };
}

export async function saveAdminApprovals(payload) {
    const result = await postLocalNormalized('/admin/approvals', payload);
    return {
        ...(result.data && typeof result.data === 'object' ? result.data : {}),
        success: result.success,
        message: result.message,
        errors: result.errors,
        meta: result.meta,
    };
}

export async function fetchAdminAssets() {
    const result = await getLocalNormalized('/admin/assets');
    const data = result.data && typeof result.data === 'object' ? result.data : {};

    return {
        assets: Array.isArray(data.rows) ? data.rows : [],
    };
}

export async function fetchAdminSettings() {
    const result = await getLocalNormalized('/admin/settings');
    return result.data;
}

export async function saveAdminSettings(payload) {
    const result = await postLocalNormalized('/admin/settings', {
        settings: payload,
    });
    return {
        ...(result.data && typeof result.data === 'object' ? result.data : {}),
        success: result.success,
        message: result.message,
        errors: result.errors,
        meta: result.meta,
    };
}

export async function linkAdminPerson(payload) {
    const result = await postLocalNormalized('/admin/link-person', {
        user_id: payload?.user_id,
        employee_code: payload?.person_id || payload?.employee_code || '',
    });
    return {
        ...(result.data && typeof result.data === 'object' ? result.data : {}),
        success: result.success,
        message: result.message,
        errors: result.errors,
        meta: result.meta,
    };
}
