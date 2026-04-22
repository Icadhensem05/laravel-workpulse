import { getLocalNormalized, postLocalNormalized } from './client';

export async function fetchLeaveBalances(year) {
    const result = await getLocalNormalized('/leave/balances', { params: { year } });
    return result.data;
}

export async function fetchLeaveList(params) {
    const result = await getLocalNormalized('/leave/requests', { params });
    return result.data;
}

export async function createLeaveApplication(payload) {
    const partDay = payload?.part_day === 'am' ? 'half_am' : payload?.part_day === 'pm' ? 'half_pm' : (payload?.part_day || 'full');
    const result = await postLocalNormalized('/leave/requests', {
        ...payload,
        part_day: partDay,
    });
    return {
        ...(result.data && typeof result.data === 'object' ? result.data : {}),
        success: result.success,
        message: result.message,
        errors: result.errors,
        meta: result.meta,
    };
}

export async function updateLeaveStatus(payload) {
    const result = await postLocalNormalized(`/leave/requests/${payload?.id}/status`, {
        status: payload?.status,
        comment: payload?.comment || '',
    });
    return {
        ...(result.data && typeof result.data === 'object' ? result.data : {}),
        success: result.success,
        message: result.message,
        errors: result.errors,
        meta: result.meta,
    };
}

export async function fetchLeaveAllocationList(year) {
    const result = await getLocalNormalized('/leave/allocations', { params: { year } });
    return result.data;
}

export async function saveLeaveAllocation(payload) {
    const result = await postLocalNormalized('/leave/allocations', payload);
    return {
        ...(result.data && typeof result.data === 'object' ? result.data : {}),
        success: result.success,
        message: result.message,
        errors: result.errors,
        meta: result.meta,
    };
}

export async function seedLeaveAllocationDefaults(year) {
    const result = await postLocalNormalized('/leave/allocations/seed-defaults', { year });
    return {
        ...(result.data && typeof result.data === 'object' ? result.data : {}),
        success: result.success,
        message: result.message,
        errors: result.errors,
        meta: result.meta,
    };
}
