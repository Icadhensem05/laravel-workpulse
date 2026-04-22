import {
    fetchAdminApprovals,
    fetchAdminAssets,
    fetchAdminSettings,
    fetchAdminUsers,
    linkAdminPerson,
    saveAdminApprovals,
    saveAdminSettings,
} from '../api/admin';
import { mapApiError } from '../api/client';
import { showGlobalFeedback } from '../ui/feedback';
import { alertHtml, emptyStateHtml } from '../ui/states';

function escapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#39;');
}

function badgeVariant(status) {
    const normalized = String(status || '').toLowerCase();
    if (normalized.includes('active')) return 'success';
    if (normalized.includes('admin')) return 'info';
    if (normalized.includes('suspend')) return 'warning';
    return 'neutral';
}

export function initAdminPage() {
    const usersRoot = document.querySelector('[data-admin-users-rows]');
    const approvalsRoot = document.querySelector('[data-admin-approvals-rows]');
    const assetsRoot = document.querySelector('[data-admin-assets-rows]');

    if (!usersRoot || !approvalsRoot || !assetsRoot) {
        return;
    }

    const state = {
        users: [],
        approvals: [],
        assets: [],
    };
    const usersCountRoot = document.querySelector('[data-admin-users-count]');
    const approvalsCountRoot = document.querySelector('[data-admin-approvals-count]');
    const assetsCountRoot = document.querySelector('[data-admin-assets-count]');

    function renderUserOptions() {
        const select = document.querySelector('[data-admin-person-user]');
        if (!select) {
            return;
        }

        const options = ['<option value="">Select a user</option>']
            .concat(state.users.map((user) => `<option value="${user.id}">${escapeHtml(user.name)} (${escapeHtml(user.email)})</option>`));
        select.innerHTML = options.join('');
    }

    function renderUsers() {
        if (usersCountRoot) {
            usersCountRoot.textContent = String(state.users.length);
        }
        usersRoot.innerHTML = state.users.length
            ? state.users.map((user) => `
                <tr>
                    <td data-label="Name" class="font-semibold text-white">${escapeHtml(user.name)}</td>
                    <td data-label="Email">${escapeHtml(user.email)}</td>
                    <td data-label="Role"><span class="wp-badge wp-badge-${badgeVariant(user.role)}">${escapeHtml(user.role)}</span></td>
                    <td data-label="Status"><span class="wp-badge wp-badge-${badgeVariant(user.status_label || user.status)}">${escapeHtml(user.status_label || user.status)}</span></td>
                </tr>
            `).join('')
            : `<tr><td colspan="4">${emptyStateHtml('No users', 'No user records are available.')}</td></tr>`;
    }

    function renderApprovals() {
        if (approvalsCountRoot) {
            approvalsCountRoot.textContent = String(state.approvals.length);
        }
        approvalsRoot.innerHTML = state.approvals.length
            ? state.approvals.map((row) => `
                <tr>
                    <td data-label="Module" class="font-semibold text-white">${escapeHtml(row.module)}</td>
                    <td data-label="Key">${escapeHtml(row.setting_key)}</td>
                    <td data-label="Value" colspan="2">
                        <input class="wp-input" data-admin-approval-value="${row.id}" value="${escapeHtml(row.setting_value || '')}">
                    </td>
                    <td data-label="Action"><button type="button" class="wp-btn-secondary" data-admin-approval-save="${row.id}">Save</button></td>
                </tr>
            `).join('')
            : `<tr><td colspan="5">${emptyStateHtml('No approvals', 'No approval rows are available.')}</td></tr>`;
    }

    function renderAssets() {
        if (assetsCountRoot) {
            assetsCountRoot.textContent = String(state.assets.length);
        }
        assetsRoot.innerHTML = state.assets.length
            ? state.assets.map((asset) => `
                <tr>
                    <td data-label="Asset" class="font-semibold text-white">${escapeHtml(asset.name)}</td>
                    <td data-label="Plate">${escapeHtml(asset.plate_no || '-')}</td>
                    <td data-label="Status"><span class="wp-badge wp-badge-${badgeVariant(asset.status)}">${escapeHtml(asset.status || '-')}</span></td>
                </tr>
            `).join('')
            : `<tr><td colspan="3">${emptyStateHtml('No assets', 'No admin asset records are available.')}</td></tr>`;
    }

    function applySettings(settings) {
        const setChecked = (selector, checked) => {
            const input = document.querySelector(selector);
            if (input) input.checked = Boolean(checked);
        };
        const setValue = (selector, value) => {
            const input = document.querySelector(selector);
            if (input) input.value = String(value || '').slice(0, 5);
        };

        setChecked('[data-admin-setting-checkin]', settings?.checkin_reminders);
        setChecked('[data-admin-setting-break]', settings?.break_alerts);
        setChecked('[data-admin-setting-weekly]', settings?.weekly_report_email);
        setChecked('[data-admin-setting-overtime]', settings?.overtime_enabled);
        setValue('[data-admin-setting-start]', settings?.start_time);
        setValue('[data-admin-setting-end]', settings?.end_time);
    }

    async function loadUsers() {
        const payload = await fetchAdminUsers();
        state.users = Array.isArray(payload?.users) ? payload.users : [];
        renderUsers();
        renderUserOptions();
    }

    async function loadApprovals() {
        const payload = await fetchAdminApprovals();
        state.approvals = Array.isArray(payload?.rows) ? payload.rows : [];
        renderApprovals();
    }

    async function loadAssets() {
        const payload = await fetchAdminAssets();
        state.assets = Array.isArray(payload?.assets) ? payload.assets : [];
        renderAssets();
    }

    async function loadSettings() {
        const payload = await fetchAdminSettings();
        applySettings(payload?.settings || {});
    }

    async function refreshAdminWorkspace() {
        try {
            await Promise.all([loadUsers(), loadApprovals(), loadAssets(), loadSettings()]);
        } catch (error) {
            const message = mapApiError(error);
            usersRoot.innerHTML = `<tr><td colspan="4">${alertHtml(message)}</td></tr>`;
            approvalsRoot.innerHTML = `<tr><td colspan="5">${alertHtml(message)}</td></tr>`;
            assetsRoot.innerHTML = `<tr><td colspan="3">${alertHtml(message)}</td></tr>`;
            showGlobalFeedback(message, 'danger', { persist: true, clearAfter: 0 });
        }
    }

    approvalsRoot.addEventListener('click', async (event) => {
        const button = event.target.closest('[data-admin-approval-save]');
        if (!button) {
            return;
        }

        const userId = Number(button.getAttribute('data-admin-approval-save'));
        const row = state.approvals.find((item) => Number(item.id) === userId);
        const value = document.querySelector(`[data-admin-approval-value="${userId}"]`)?.value || '';

        if (!row) {
            return;
        }

        try {
            await saveAdminApprovals({
                rows: [{
                    module: row.module,
                    setting_key: row.setting_key,
                    setting_value: value,
                }],
            });
            showGlobalFeedback('Approval setting saved successfully.', 'success');
            await loadApprovals();
        } catch (error) {
            showGlobalFeedback(mapApiError(error), 'danger');
        }
    });

    document.querySelector('[data-admin-settings-save]')?.addEventListener('click', async () => {
        try {
            await saveAdminSettings({
                checkin_reminders: document.querySelector('[data-admin-setting-checkin]')?.checked ? 1 : 0,
                break_alerts: document.querySelector('[data-admin-setting-break]')?.checked ? 1 : 0,
                weekly_report_email: document.querySelector('[data-admin-setting-weekly]')?.checked ? 1 : 0,
                overtime_enabled: document.querySelector('[data-admin-setting-overtime]')?.checked ? 1 : 0,
                start_time: document.querySelector('[data-admin-setting-start]')?.value || '09:00',
                end_time: document.querySelector('[data-admin-setting-end]')?.value || '18:00',
            });
            showGlobalFeedback('Settings updated successfully.', 'success');
        } catch (error) {
            showGlobalFeedback(mapApiError(error), 'danger');
        }
    });

    document.querySelector('[data-admin-person-link]')?.addEventListener('click', async () => {
        const personId = document.querySelector('[data-admin-person-id]')?.value?.trim() || '';
        const userId = Number(document.querySelector('[data-admin-person-user]')?.value || 0);

        if (!personId || !userId) {
            showGlobalFeedback('Person ID and target user are required.', 'warning');
            return;
        }

        try {
            await linkAdminPerson({ person_id: personId, user_id: userId });
            showGlobalFeedback('Person mapping linked successfully.', 'success');
            const personInput = document.querySelector('[data-admin-person-id]');
            if (personInput) personInput.value = '';
        } catch (error) {
            showGlobalFeedback(mapApiError(error), 'danger');
        }
    });

    document.querySelector('[data-admin-refresh]')?.addEventListener('click', refreshAdminWorkspace);

    refreshAdminWorkspace();
}
