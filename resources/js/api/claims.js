import {
    deleteLocalNormalized,
    getLocalNormalized,
    postLocalMultipartNormalized,
    postLocalNormalized,
    putLocalNormalized,
} from './client';

export async function fetchClaimsList(params) {
    const result = await getLocalNormalized('/claims', { params });
    const data = result.data && typeof result.data === 'object' ? result.data : {};
    const month = String(params?.month || '');
    const search = String(params?.search || '').trim().toLowerCase();
    const claims = Array.isArray(data.claims) ? data.claims.filter((claim) => {
        const monthMatches = !month || String(claim.claim_month || '') === month;
        const haystack = [
            claim.claim_no,
            claim.employee_name,
            claim.employee_code,
        ].join(' ').toLowerCase();
        const searchMatches = !search || haystack.includes(search);

        return monthMatches && searchMatches;
    }).map((claim) => ({
        ...claim,
        item_count: Number(claim.item_count || 0),
        attachment_count: Number(claim.attachment_count || 0),
    })) : [];

    return {
        claims,
        categories: data.categories || [],
        default_mileage_rate: Number(data.default_mileage_rate || 0),
        summary: {
            total: claims.length,
            grand_total: claims.reduce((sum, claim) => sum + Number(claim.grand_total || 0), 0),
        },
    };
}

export async function fetchClaimDetail(id) {
    const result = await getLocalNormalized(`/claims/${id}`);
    const data = result.data && typeof result.data === 'object' ? result.data : {};

    return {
        ...data,
        default_mileage_rate: Number(data.default_mileage_rate || 0),
    };
}

export async function saveClaimDraft(payload) {
    const claimId = Number(payload?.claim_id || 0);
    const body = {
        employee_code: payload?.employee_code || '',
        position_title: payload?.position_title || '',
        department: payload?.department || '',
        cost_center: payload?.cost_center || '',
        claim_month: payload?.claim_month || '',
        claim_date: payload?.claim_date || '',
        advance_amount: payload?.advance_amount ?? 0,
        employee_remarks: payload?.employee_remarks || '',
        items: payload?.items || [],
    };
    const result = claimId > 0
        ? await putLocalNormalized(`/claims/${claimId}`, body)
        : await postLocalNormalized('/claims', body);
    return {
        ...(result.data && typeof result.data === 'object' ? result.data : {}),
        success: result.success,
        message: result.message,
        errors: result.errors,
        meta: result.meta,
    };
}

export async function submitClaim(claimId) {
    const result = await postLocalNormalized(`/claims/${claimId}/submit`, {});
    return {
        ...(result.data && typeof result.data === 'object' ? result.data : {}),
        success: result.success,
        message: result.message,
        errors: result.errors,
        meta: result.meta,
    };
}

export async function runClaimAction(payload) {
    const actionMap = {
        approve_manager: 'manager_approve',
        reject_manager: 'manager_reject',
        return_manager: 'manager_return',
        approve_finance: 'finance_approve',
        mark_paid: 'mark_paid',
    };
    const result = await postLocalNormalized(`/claims/${payload?.claim_id}/action`, {
        ...payload,
        action: actionMap[payload?.action] || payload?.action,
    });
    return {
        ...(result.data && typeof result.data === 'object' ? result.data : {}),
        success: result.success,
        message: result.message,
        errors: result.errors,
        meta: result.meta,
    };
}

export async function uploadClaimAttachments(claimId, files, onUploadProgress = null) {
    let lastResult = null;
    const items = Array.from(files || []);

    for (let index = 0; index < items.length; index += 1) {
        const form = new FormData();
        form.append('file', items[index]);

        lastResult = await postLocalMultipartNormalized(`/claims/${claimId}/attachments`, form, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
            onUploadProgress: onUploadProgress
                ? (event) => {
                    const total = Number(event?.total || 0);
                    const loaded = Number(event?.loaded || 0);
                    const aggregateLoaded = (index * total) + loaded;
                    const aggregateTotal = items.length * total;
                    onUploadProgress({
                        loaded: aggregateLoaded,
                        total: aggregateTotal,
                    });
                }
                : null,
        });
    }

    return {
        ...(lastResult?.data && typeof lastResult.data === 'object' ? lastResult.data : {}),
        success: lastResult?.success ?? true,
        message: lastResult?.message ?? 'Attachments uploaded successfully.',
        errors: lastResult?.errors ?? [],
        meta: lastResult?.meta ?? {},
    };
}

export async function extractClaimReceipt(file, onUploadProgress = null) {
    const form = new FormData();
    form.append('file', file);

    const result = await postLocalMultipartNormalized('/claims/receipt-extract', form, {
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

export async function deleteClaimAttachment(claimId, attachmentId) {
    const result = await deleteLocalNormalized(`/claims/${claimId}/attachments/${attachmentId}`);
    return {
        ...(result.data && typeof result.data === 'object' ? result.data : {}),
        success: result.success,
        message: result.message,
        errors: result.errors,
        meta: result.meta,
    };
}
