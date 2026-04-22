import { getLocalNormalized, postLocalNormalized } from './client';

export async function loginWithPassword(payload) {
    const result = await postLocalNormalized('/auth/login', payload);
    return {
        ...(result.data && typeof result.data === 'object' ? result.data : {}),
        success: result.success,
        message: result.message,
        errors: result.errors,
        meta: result.meta,
    };
}

export async function requestPasswordReset(payload) {
    const result = await postLocalNormalized('/auth/forgot-password', payload);
    return {
        ...(result.data && typeof result.data === 'object' ? result.data : {}),
        success: result.success,
        message: result.message,
        errors: result.errors,
        meta: result.meta,
    };
}

export async function checkPasswordResetToken(token) {
    const result = await getLocalNormalized('/auth/reset-password/check', { params: { token } });
    return result.data;
}

export async function completePasswordReset(payload) {
    const result = await postLocalNormalized('/auth/reset-password', {
        ...payload,
        password_confirmation: payload.password,
    });
    return {
        ...(result.data && typeof result.data === 'object' ? result.data : {}),
        success: result.success,
        message: result.message,
        errors: result.errors,
        meta: result.meta,
    };
}

export async function logoutCurrentSession() {
    const result = await postLocalNormalized('/auth/logout');
    return {
        ...(result.data && typeof result.data === 'object' ? result.data : {}),
        success: result.success,
        message: result.message,
        errors: result.errors,
        meta: result.meta,
    };
}
