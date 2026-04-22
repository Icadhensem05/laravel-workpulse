import { getLocalNormalized, postLocalMultipartNormalized, putLocalNormalized } from './client';

export async function fetchProfile() {
    const result = await getLocalNormalized('/profile');
    return result.data;
}

export async function updateProfile(payload) {
    const result = await putLocalNormalized('/profile', payload);
    return {
        ...(result.data && typeof result.data === 'object' ? result.data : {}),
        success: result.success,
        message: result.message,
        errors: result.errors,
        meta: result.meta,
    };
}

export async function updatePassword(payload) {
    const result = await putLocalNormalized('/profile/password', payload);
    return {
        ...(result.data && typeof result.data === 'object' ? result.data : {}),
        success: result.success,
        message: result.message,
        errors: result.errors,
        meta: result.meta,
    };
}

export async function uploadProfilePhoto(file, onUploadProgress = null) {
    const form = new FormData();
    form.append('photo', file);

    const result = await postLocalMultipartNormalized('/profile/photo', form, {
        headers: {
            'Content-Type': 'multipart/form-data',
        },
        onUploadProgress,
    });

    return {
        ...(result.data && typeof result.data === 'object' ? result.data : {}),
        success: result.success,
        message: result.message,
        errors: result.errors,
        meta: result.meta,
    };
}
