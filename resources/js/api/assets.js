import { getLocalNormalized, postLocalNormalized } from './client';

export async function fetchAssetsList() {
    const result = await getLocalNormalized('/assets');
    const data = result.data && typeof result.data === 'object' ? result.data : {};

    return {
        assets: Array.isArray(data.rows) ? data.rows.map((asset) => ({
            ...asset,
            plate_no: asset.asset_code || '',
            description: asset.assigned_to_name || asset.remarks || '',
        })) : [],
    };
}

export async function createAsset(payload) {
    const result = await postLocalNormalized('/assets', {
        asset_code: payload?.plate_no || payload?.asset_code || payload?.name || '',
        name: payload?.name || '',
        category: payload?.category || 'general',
        remarks: payload?.description || payload?.remarks || '',
    });
    return {
        ...(result.data && typeof result.data === 'object' ? result.data : {}),
        success: result.success,
        message: result.message,
        errors: result.errors,
        meta: result.meta,
    };
}

export async function updateAssetStatus(payload) {
    const status = payload?.action === 'activate' ? 'available' : 'maintenance';
    const result = await postLocalNormalized(`/assets/${payload?.id}/status`, {
        status,
        remarks: payload?.remarks || null,
    });
    return {
        ...(result.data && typeof result.data === 'object' ? result.data : {}),
        success: result.success,
        message: result.message,
        errors: result.errors,
        meta: result.meta,
    };
}
