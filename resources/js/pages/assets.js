import { createAsset, fetchAssetsList, updateAssetStatus } from '../api/assets';
import { mapApiError } from '../api/client';
import { alertHtml, emptyStateHtml } from '../ui/states';
import { showGlobalFeedback } from '../ui/feedback';

function escapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#39;');
}

function downloadCsv(filename, headers, rows) {
    const csvRows = [
        headers.join(','),
        ...rows.map((row) => row.map((value) => `"${String(value ?? '').replaceAll('"', '""')}"`).join(',')),
    ];
    const blob = new Blob([csvRows.join('\n')], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    link.remove();
    URL.revokeObjectURL(url);
}

export function initAssetsPage() {
    const root = document.querySelector('[data-assets-grid]');
    if (!root) return;
    const state = { assets: [] };

    function openDetailModal(asset) {
        const modal = document.querySelector('#assetDetailModal');
        if (!modal) {
            return;
        }

        const nameRoot = modal.querySelector('[data-asset-detail-name]');
        const typeRoot = modal.querySelector('[data-asset-detail-type]');
        const assignedRoot = modal.querySelector('[data-asset-detail-assigned]');
        const statusRoot = modal.querySelector('[data-asset-detail-status]');

        if (nameRoot) nameRoot.textContent = asset.name || 'Asset';
        if (typeRoot) typeRoot.textContent = asset.type || 'Type';
        if (assignedRoot) assignedRoot.value = asset.assigned || '-';
        if (statusRoot) statusRoot.value = asset.status || '-';

        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
    }

    async function loadAssets() {
        try {
            const payload = await fetchAssetsList();
            const assets = payload?.assets || [];
            state.assets = assets;
            root.innerHTML = assets.length ? assets.map((asset) => `
                <article class="wp-panel p-6" data-asset-card data-asset-name="${escapeHtml(asset.name)}" data-asset-type="${escapeHtml(asset.plate_no || 'Asset')}" data-asset-assigned="${escapeHtml(asset.description || '-')}" data-asset-status="${escapeHtml(asset.status)}">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="wp-label">${escapeHtml(asset.plate_no || 'Asset')}</p>
                            <p class="mt-4 text-xl font-semibold tracking-tight text-white">${escapeHtml(asset.name)}</p>
                            <p class="wp-helper mt-3">${escapeHtml(asset.description || '-')}</p>
                        </div>
                        <span class="wp-badge wp-badge-${asset.status === 'active' ? 'success' : 'warning'}">${escapeHtml(asset.status)}</span>
                    </div>
                    <div class="mt-4 flex flex-wrap gap-2">
                        <button type="button" class="wp-btn-ghost" data-asset-detail-open>View Details</button>
                        <button type="button" class="wp-btn-secondary" data-asset-action="${asset.id}" data-asset-status="deactivate">Deactivate</button>
                        <button type="button" class="wp-btn-secondary" data-asset-action="${asset.id}" data-asset-status="activate">Activate</button>
                    </div>
                </article>
            `).join('') : emptyStateHtml('No assets', 'No active assets are available in the current dataset.');
        } catch (error) {
            const message = mapApiError(error);
            console.warn('assets api:', message);
            root.innerHTML = alertHtml(message);
        }
    }

    document.querySelector('[data-asset-create]')?.addEventListener('click', async () => {
        try {
            await createAsset({
                name: document.querySelector('[data-asset-name]')?.value || '',
                plate_no: document.querySelector('[data-asset-plate]')?.value || '',
                description: document.querySelector('[data-asset-description]')?.value || '',
            });
            document.querySelector('#assetModal')?.classList.remove('is-open');
            await loadAssets();
            showGlobalFeedback('Asset created successfully.', 'success');
        } catch (error) {
            showGlobalFeedback(mapApiError(error), 'danger');
        }
    });

    root.addEventListener('click', async (event) => {
        const detailButton = event.target.closest('[data-asset-detail-open]');
        if (detailButton) {
            const card = detailButton.closest('[data-asset-card]');
            if (card) {
                openDetailModal({
                    name: card.getAttribute('data-asset-name'),
                    type: card.getAttribute('data-asset-type'),
                    assigned: card.getAttribute('data-asset-assigned'),
                    status: card.getAttribute('data-asset-status'),
                });
            }
            return;
        }

        const button = event.target.closest('[data-asset-action]');
        if (!button) return;
        try {
            await updateAssetStatus({
                id: Number(button.getAttribute('data-asset-action')),
                action: button.getAttribute('data-asset-status'),
            });
            await loadAssets();
            showGlobalFeedback('Asset status updated successfully.', 'success');
        } catch (error) {
            showGlobalFeedback(mapApiError(error), 'danger');
        }
    });

    document.querySelector('[data-assets-export]')?.addEventListener('click', () => {
        downloadCsv('assets.csv', [
            'Code',
            'Name',
            'Category',
            'Status',
            'Assigned To',
            'Remarks',
        ], state.assets.map((asset) => [
            asset.asset_code || asset.plate_no || '',
            asset.name || '',
            asset.category || '',
            asset.status || '',
            asset.assigned_to_name || asset.description || '',
            asset.remarks || '',
        ]));
    });

    loadAssets();
}
