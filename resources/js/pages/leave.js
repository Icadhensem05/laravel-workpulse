import {
    createLeaveApplication,
    fetchLeaveAllocationList,
    fetchLeaveBalances,
    fetchLeaveList,
    saveLeaveAllocation,
    seedLeaveAllocationDefaults,
    updateLeaveStatus,
} from '../api/leave';
import { mapApiError } from '../api/client';

function variantForStatus(status) {
    const value = String(status || '').toLowerCase();
    if (value === 'approved') return 'success';
    if (value === 'rejected') return 'danger';
    return 'warning';
}

function escapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#39;');
}

function moneyDays(value) {
    return `${Number(value || 0).toFixed(1)}d`;
}

export function initLeavePage() {
    const balancesRoot = document.querySelector('[data-leave-balances]');
    const rowsRoot = document.querySelector('[data-leave-rows]');
    const adminRowsRoot = document.querySelector('[data-leave-admin-rows]');
    const allocationRowsRoot = document.querySelector('[data-leave-allocation-rows]');
    const feedbackRoot = document.querySelector('[data-leave-feedback]');
    const myStatusSelect = document.querySelector('[data-leave-my-status]');
    const adminStatusSelect = document.querySelector('[data-leave-admin-status]');
    const allocationYearInput = document.querySelector('[data-leave-allocation-year]');
    const adminPanel = document.querySelector('[data-leave-admin-panel]');
    const allocationPanel = document.querySelector('[data-leave-allocation-panel]');
    const adminBadge = document.querySelector('[data-leave-admin-badge]');

    if (!balancesRoot || !rowsRoot || !adminRowsRoot || !allocationRowsRoot || !feedbackRoot || !myStatusSelect || !adminStatusSelect || !allocationYearInput) {
        return;
    }

    const currentYear = Number(new Date().getFullYear());
    const state = {
        adminEnabled: true,
        allocationEnabled: true,
        year: Number(allocationYearInput.value || currentYear),
    };

    function showFeedback(message, type = 'info') {
        feedbackRoot.className = 'wp-alert';
        feedbackRoot.classList.remove('hidden', 'wp-alert-info', 'wp-alert-success', 'wp-alert-warning', 'wp-alert-danger');
        feedbackRoot.classList.add(`wp-alert-${type}`);
        feedbackRoot.textContent = message;
    }

    function renderBalances(payload) {
        if (!Array.isArray(payload?.balances) || payload.balances.length === 0) {
            balancesRoot.innerHTML = '<section class="wp-panel p-6 lg:col-span-3"><p class="wp-helper">No leave balances found for the selected year.</p></section>';
            return;
        }

        balancesRoot.innerHTML = payload.balances.map((balance) => `
            <section class="wp-panel p-6">
                <p class="wp-label">${String(balance.leave_type || '').replace(/\b\w/g, (match) => match.toUpperCase())}</p>
                <p class="mt-5 text-3xl font-semibold tracking-tight text-white">${moneyDays(balance.balance)}</p>
                <div class="mt-5 space-y-2 text-sm text-ink-300">
                    <p>Eligible: ${moneyDays(balance.eligible)}</p>
                    <p>Taken: ${moneyDays(balance.taken)}</p>
                </div>
            </section>
        `).join('');
    }

    function renderMyRows(rows) {
        rowsRoot.innerHTML = rows.length ? rows.map((row) => `
            <tr>
                <td data-label="Period">${escapeHtml(row.start_date)} -> ${escapeHtml(row.end_date)}</td>
                <td data-label="Type">${escapeHtml(row.leave_type)}${row.part_day && row.part_day !== 'full' ? ` (${escapeHtml(row.part_day)})` : ''}</td>
                <td data-label="Reason">${escapeHtml(row.reason || '-')}</td>
                <td data-label="Status"><span class="wp-badge wp-badge-${variantForStatus(row.status)}">${escapeHtml(row.status)}</span></td>
                <td data-label="Submitted">${escapeHtml(row.created_at || '-')}</td>
                <td data-label="Decision">${row.decided_at ? `${escapeHtml(row.status)}${row.admin_comment ? ` - ${escapeHtml(row.admin_comment)}` : ''}` : '-'}</td>
                <td data-label="Form"><a class="wp-btn-secondary" href="${document.documentElement.dataset.legacyApiBaseUrl || 'https://workpulse.weststar-dev.com/api'}/leave_form.php?id=${row.id}" target="_blank" rel="noreferrer">Print</a></td>
            </tr>
        `).join('') : '<tr><td colspan="7" class="py-8 text-center text-sm text-ink-400">No leave applications found.</td></tr>';
    }

    function renderAdminRows(rows) {
        adminRowsRoot.innerHTML = rows.length ? rows.map((row) => `
            <article class="rounded-3xl border border-white/8 bg-white/[0.03] p-5">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <p class="text-sm font-semibold text-white">${escapeHtml(row.user_name || 'Employee')}</p>
                        <p class="wp-helper mt-1">${escapeHtml(row.start_date)} -> ${escapeHtml(row.end_date)} · ${escapeHtml(row.leave_type)}${row.part_day && row.part_day !== 'full' ? ` (${escapeHtml(row.part_day)})` : ''}</p>
                        <p class="wp-helper mt-2">${escapeHtml(row.reason || '-')}</p>
                    </div>
                    <span class="wp-badge wp-badge-${variantForStatus(row.status)}">${escapeHtml(row.status)}</span>
                </div>
                <div class="mt-4 flex flex-wrap gap-3">
                    <button type="button" class="wp-btn-secondary" data-leave-approve="${row.id}">Approve</button>
                    <button type="button" class="wp-btn-danger" data-leave-reject="${row.id}">Reject</button>
                    <a class="wp-btn-ghost" href="${document.documentElement.dataset.legacyApiBaseUrl || 'https://workpulse.weststar-dev.com/api'}/leave_form.php?id=${row.id}" target="_blank" rel="noreferrer">Print Form</a>
                </div>
            </article>
        `).join('') : '<p class="wp-helper">No leave requests in this filter.</p>';

        adminBadge.textContent = `${rows.length} item${rows.length === 1 ? '' : 's'}`;
    }

    function renderAllocationRows(payload) {
        const types = payload?.types || [];
        const rows = payload?.rows || [];

        allocationRowsRoot.innerHTML = rows.length ? rows.map((row) => {
            const inputs = types.map((type) => `
                <td data-label="${escapeHtml(type)}">
                    <input class="wp-input min-w-20" type="number" step="0.1" min="0" data-leave-alloc-field="${escapeHtml(type)}" value="${escapeHtml(row.alloc?.[type] ?? 0)}">
                </td>
            `).join('');

            return `
                <tr data-leave-alloc-row="${row.user_id}">
                    <td data-label="User">${escapeHtml(row.name)}</td>
                    <td data-label="Email">${escapeHtml(row.email)}</td>
                    ${inputs}
                    <td data-label="Action"><button type="button" class="wp-btn-secondary" data-leave-alloc-save="${row.user_id}">Save</button></td>
                </tr>
            `;
        }).join('') : '<tr><td colspan="11" class="py-8 text-center text-sm text-ink-400">No allocation rows available.</td></tr>';
    }

    async function loadBalances() {
        const payload = await fetchLeaveBalances(state.year);
        renderBalances(payload);
    }

    async function loadMyApplications() {
        const payload = await fetchLeaveList({
            my: 1,
            status: myStatusSelect.value || 'all',
            limit: 20,
        });
        renderMyRows(payload?.rows || []);
    }

    async function loadAdminInbox() {
        try {
            const payload = await fetchLeaveList({
                status: adminStatusSelect.value || 'pending',
                limit: 20,
            });
            state.adminEnabled = true;
            adminPanel?.classList.remove('hidden');
            renderAdminRows(payload?.rows || []);
        } catch (error) {
            if (String(mapApiError(error)).toLowerCase().includes('forbidden')) {
                state.adminEnabled = false;
                adminPanel?.classList.add('hidden');
                return;
            }
            throw error;
        }
    }

    async function loadAllocations() {
        try {
            const payload = await fetchLeaveAllocationList(state.year);
            state.allocationEnabled = true;
            allocationPanel?.classList.remove('hidden');
            renderAllocationRows(payload);
        } catch (error) {
            if (String(mapApiError(error)).toLowerCase().includes('forbidden')) {
                state.allocationEnabled = false;
                allocationPanel?.classList.add('hidden');
                return;
            }
            throw error;
        }
    }

    async function refreshAll() {
        try {
            await Promise.all([
                loadBalances(),
                loadMyApplications(),
                loadAdminInbox(),
                loadAllocations(),
            ]);
        } catch (error) {
            showFeedback(mapApiError(error), 'danger');
        }
    }

    document.querySelector('[data-leave-submit]')?.addEventListener('click', async () => {
        const payload = {
            start_date: document.querySelector('#leaveStart')?.value || '',
            end_date: document.querySelector('#leaveEnd')?.value || '',
            leave_type: document.querySelector('#leaveType')?.value || 'annual',
            part_day: document.querySelector('#leavePart')?.value || 'full',
            person_to_relief: document.querySelector('#leaveRelief')?.value || '',
            reason: document.querySelector('#leaveReason')?.value || '',
        };

        try {
            await createLeaveApplication(payload);
            document.querySelector('#leaveApplyModal')?.classList.remove('is-open');
            showFeedback('Leave application submitted.', 'success');
            await Promise.all([loadBalances(), loadMyApplications()]);
        } catch (error) {
            showFeedback(mapApiError(error), 'danger');
        }
    });

    myStatusSelect.addEventListener('change', loadMyApplications);
    adminStatusSelect.addEventListener('change', loadAdminInbox);
    document.querySelector('[data-leave-admin-refresh]')?.addEventListener('click', loadAdminInbox);

    document.querySelector('[data-leave-allocation-refresh]')?.addEventListener('click', async () => {
        state.year = Number(allocationYearInput.value || currentYear);
        await Promise.all([loadBalances(), loadAllocations()]);
    });

    document.querySelector('[data-leave-seed-defaults]')?.addEventListener('click', async () => {
        try {
            await seedLeaveAllocationDefaults(Number(allocationYearInput.value || currentYear));
            showFeedback('Default leave allocation seeded.', 'success');
            await loadAllocations();
        } catch (error) {
            showFeedback(mapApiError(error), 'danger');
        }
    });

    adminRowsRoot.addEventListener('click', async (event) => {
        const approveButton = event.target.closest('[data-leave-approve]');
        const rejectButton = event.target.closest('[data-leave-reject]');
        const id = approveButton?.getAttribute('data-leave-approve') || rejectButton?.getAttribute('data-leave-reject');
        if (!id) {
            return;
        }

        const status = approveButton ? 'approved' : 'rejected';
        const comment = window.prompt(`Comment for ${status} leave request:`, '') ?? '';

        try {
            await updateLeaveStatus({
                id: Number(id),
                status,
                comment,
            });
            showFeedback(`Leave request ${status}.`, 'success');
            await Promise.all([loadAdminInbox(), loadMyApplications(), loadBalances()]);
        } catch (error) {
            showFeedback(mapApiError(error), 'danger');
        }
    });

    allocationRowsRoot.addEventListener('click', async (event) => {
        const button = event.target.closest('[data-leave-alloc-save]');
        if (!button) {
            return;
        }

        const userId = Number(button.getAttribute('data-leave-alloc-save'));
        const row = allocationRowsRoot.querySelector(`[data-leave-alloc-row="${userId}"]`);
        if (!row) {
            return;
        }

        const alloc = {};
        row.querySelectorAll('[data-leave-alloc-field]').forEach((field) => {
            alloc[field.getAttribute('data-leave-alloc-field')] = Number(field.value || 0);
        });

        try {
            await saveLeaveAllocation({
                user_id: userId,
                year: Number(allocationYearInput.value || currentYear),
                alloc,
            });
            showFeedback('Leave allocation saved.', 'success');
        } catch (error) {
            showFeedback(mapApiError(error), 'danger');
        }
    });

    refreshAll();
}
