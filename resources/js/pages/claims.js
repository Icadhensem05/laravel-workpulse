import {
    deleteClaimAttachment,
    extractClaimReceipt,
    fetchClaimDetail,
    fetchClaimsList,
    runClaimAction,
    saveClaimDraft,
    submitClaim,
    uploadClaimAttachments,
} from '../api/claims';
import { mapApiError } from '../api/client';
import { showGlobalFeedback } from '../ui/feedback';

const CATEGORY_ORDER = [
    { code: 'travelling', label: 'TRAVELLING' },
    { code: 'transportation', label: 'TRANSPORTATION' },
    { code: 'accommodation', label: 'ACCOMODATION' },
    { code: 'travelling_allowance', label: 'TRAVELLING ALLOWANCE' },
    { code: 'entertainment', label: 'ENTERTAINMENT/REFRESHMENT' },
    { code: 'miscellaneous', label: 'OTHERS' },
];

function money(value) {
    return `RM ${Number(value || 0).toFixed(2)}`;
}

function numberValue(value, fallback = 0) {
    const parsed = Number(value);
    return Number.isFinite(parsed) ? parsed : fallback;
}

function escapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#39;');
}

function setInputValue(selector, value) {
    const element = document.querySelector(selector);
    if (element) {
        element.value = value ?? '';
    }
}

function setText(selector, value) {
    const element = document.querySelector(selector);
    if (element) {
        element.textContent = value ?? '';
    }
}

function blankItem(categoryCode = 'travelling', mileageRate = 0) {
    return {
        category_code: categoryCode,
        item_date: new Date().toISOString().slice(0, 10),
        from_location: '',
        to_location: '',
        purpose: '',
        receipt_no: '',
        invoice_no: '',
        hotel_name: '',
        description: '',
        distance_km: 0,
        mileage_rate: categoryCode === 'travelling' ? mileageRate : 0,
        toll_amount: 0,
        parking_amount: 0,
        rate_amount: 0,
        quantity_value: 1,
        amount: 0,
        total_amount: 0,
        remarks: '',
    };
}

function normalizeItem(item, mileageRate) {
    return {
        ...blankItem(item?.category_code || 'travelling', mileageRate),
        ...item,
    };
}

function defaultClaim(me) {
    const today = new Date();
    const claimMonth = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}`;
    const claimDate = today.toISOString().slice(0, 10);

    return {
        id: null,
        claim_no: '',
        employee_name: me?.name || '',
        employee_code: me?.employee_code || '',
        position_title: me?.job_title || '',
        department: me?.department || '',
        cost_center: me?.cost_center || '',
        claim_month: claimMonth,
        claim_date: claimDate,
        advance_amount: 0,
        balance_claim: 0,
        grand_total: 0,
        employee_remarks: '',
        permissions: {
            can_edit: true,
            can_submit: false,
            can_upload: false,
            can_manager_review: false,
            can_finance_review: false,
            can_mark_paid: false,
        },
    };
}

function computeItem(item) {
    const normalized = normalizeItem(item, 0);
    const code = normalized.category_code;
    const distance = Math.max(0, numberValue(normalized.distance_km));
    const mileageRate = Math.max(0, numberValue(normalized.mileage_rate));
    const toll = Math.max(0, numberValue(normalized.toll_amount));
    const parking = Math.max(0, numberValue(normalized.parking_amount));
    const rateAmount = Math.max(0, numberValue(normalized.rate_amount));
    const quantity = Math.max(0, numberValue(normalized.quantity_value, 1));
    const amount = Math.max(0, numberValue(normalized.amount));

    if (code === 'travelling') {
        const mileageAmount = Number((distance * mileageRate).toFixed(2));
        return {
            ...normalized,
            distance_km: distance,
            mileage_rate: mileageRate,
            mileage_amount: mileageAmount,
            toll_amount: toll,
            parking_amount: parking,
            amount: mileageAmount,
            total_amount: Number((mileageAmount + toll + parking).toFixed(2)),
        };
    }

    if (code === 'travelling_allowance') {
        return {
            ...normalized,
            rate_amount: rateAmount,
            quantity_value: quantity <= 0 ? 1 : quantity,
            amount: rateAmount,
            total_amount: Number((rateAmount * (quantity <= 0 ? 1 : quantity)).toFixed(2)),
        };
    }

    return {
        ...normalized,
        amount,
        total_amount: Number(amount.toFixed(2)),
    };
}

function computeSummary(items, advanceAmount) {
    const totals = {
        travelling: 0,
        transportation: 0,
        accommodation: 0,
        travelling_allowance: 0,
        entertainment: 0,
        miscellaneous: 0,
    };

    const computedItems = items.map((item) => computeItem(item));
    computedItems.forEach((item) => {
        if (Object.hasOwn(totals, item.category_code)) {
            totals[item.category_code] += numberValue(item.total_amount);
        }
    });

    Object.keys(totals).forEach((key) => {
        totals[key] = Number(totals[key].toFixed(2));
    });

    const grandTotal = Number(Object.values(totals).reduce((sum, value) => sum + value, 0).toFixed(2));
    const advance = Math.max(0, numberValue(advanceAmount));
    const balanceClaim = Math.max(0, Number((grandTotal - advance).toFixed(2)));

    return {
        items: computedItems,
        totals,
        grand_total: grandTotal,
        advance_amount: advance,
        balance_claim: balanceClaim,
    };
}

function promptRemarks(actionLabel) {
    return window.prompt(`Remarks for ${actionLabel} (optional):`, '') ?? '';
}

function openModal(modal) {
    modal?.classList.add('is-open');
}

function activateTab(key) {
    const tabList = document.querySelector('[role="tablist"]');
    if (!tabList) {
        return;
    }

    tabList.querySelectorAll('[data-tab-trigger]').forEach((trigger) => {
        const active = trigger.getAttribute('data-tab-trigger') === key;
        trigger.classList.toggle('wp-tab-active', active);
    });

    tabList.parentElement?.querySelectorAll('[data-tab-panel]').forEach((panel) => {
        panel.classList.toggle('hidden', panel.getAttribute('data-tab-panel') !== key);
    });
}

function attachmentHtml(attachment, canDelete) {
    const removeButton = canDelete
        ? `<button type="button" class="wp-btn-ghost" data-claim-attachment-delete="${attachment.id}">Remove</button>`
        : '';

    return `
        <article class="flex flex-col gap-3 rounded-3xl border border-white/8 bg-white/[0.03] p-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-sm font-semibold text-white">${escapeHtml(attachment.file_name)}</p>
                <p class="wp-helper mt-1">${escapeHtml(attachment.mime_type || 'File')} - ${Math.max(0, Math.round((attachment.file_size || 0) / 1024))} KB</p>
            </div>
            <div class="flex items-center gap-3">
                <a class="wp-btn-secondary" href="/${String(attachment.file_path || '').replace(/^\/+/, '')}" target="_blank" rel="noreferrer">Open</a>
                ${removeButton}
            </div>
        </article>
    `;
}

function itemEditorHtml(item, index, categories, canEdit) {
    const categoryOptions = categories
        .map((category) => `<option value="${escapeHtml(category.code)}" ${category.code === item.category_code ? 'selected' : ''}>${escapeHtml(category.name)}</option>`)
        .join('');
    const disabled = canEdit ? '' : 'disabled';
    const travellingFields = item.category_code === 'travelling' ? `
        <div class="grid gap-4 md:grid-cols-3">
            <label class="block space-y-2">
                <span class="wp-label">From Location</span>
                <input class="wp-input" data-item-field="from_location" value="${escapeHtml(item.from_location)}" ${disabled}>
            </label>
            <label class="block space-y-2">
                <span class="wp-label">To Location</span>
                <input class="wp-input" data-item-field="to_location" value="${escapeHtml(item.to_location)}" ${disabled}>
            </label>
            <label class="block space-y-2">
                <span class="wp-label">Purpose</span>
                <input class="wp-input" data-item-field="purpose" value="${escapeHtml(item.purpose)}" ${disabled}>
            </label>
            <label class="block space-y-2">
                <span class="wp-label">Distance (KM)</span>
                <input class="wp-input" type="number" step="0.01" min="0" data-item-field="distance_km" value="${escapeHtml(item.distance_km)}" ${disabled}>
            </label>
            <label class="block space-y-2">
                <span class="wp-label">Mileage Rate</span>
                <input class="wp-input" type="number" step="0.0001" min="0" data-item-field="mileage_rate" value="${escapeHtml(item.mileage_rate)}" ${disabled}>
            </label>
            <label class="block space-y-2">
                <span class="wp-label">Toll</span>
                <input class="wp-input" type="number" step="0.01" min="0" data-item-field="toll_amount" value="${escapeHtml(item.toll_amount)}" ${disabled}>
            </label>
            <label class="block space-y-2 md:col-span-1">
                <span class="wp-label">Parking</span>
                <input class="wp-input" type="number" step="0.01" min="0" data-item-field="parking_amount" value="${escapeHtml(item.parking_amount)}" ${disabled}>
            </label>
        </div>
    ` : '';
    const allowanceFields = item.category_code === 'travelling_allowance' ? `
        <div class="grid gap-4 md:grid-cols-3">
            <label class="block space-y-2">
                <span class="wp-label">Rate / Amount</span>
                <input class="wp-input" type="number" step="0.01" min="0" data-item-field="rate_amount" value="${escapeHtml(item.rate_amount)}" ${disabled}>
            </label>
            <label class="block space-y-2">
                <span class="wp-label">Quantity / Days</span>
                <input class="wp-input" type="number" step="0.01" min="0" data-item-field="quantity_value" value="${escapeHtml(item.quantity_value)}" ${disabled}>
            </label>
        </div>
    ` : '';
    const transportReceiptField = item.category_code === 'transportation' ? `
        <label class="block space-y-2">
            <span class="wp-label">Receipt / Invoice No</span>
            <input class="wp-input" data-item-field="receipt_no" value="${escapeHtml(item.receipt_no)}" ${disabled}>
        </label>
    ` : '';
    const accommodationField = item.category_code === 'accommodation' ? `
        <label class="block space-y-2">
            <span class="wp-label">Hotel / Accommodation Name</span>
            <input class="wp-input" data-item-field="hotel_name" value="${escapeHtml(item.hotel_name)}" ${disabled}>
        </label>
    ` : '';
    const entertainmentPurposeField = item.category_code === 'entertainment' ? `
        <label class="block space-y-2">
            <span class="wp-label">Purpose</span>
            <input class="wp-input" data-item-field="purpose" value="${escapeHtml(item.purpose)}" ${disabled}>
        </label>
    ` : '';
    const miscellaneousField = item.category_code === 'miscellaneous' ? `
        <label class="block space-y-2">
            <span class="wp-label">Invoice No</span>
            <input class="wp-input" data-item-field="invoice_no" value="${escapeHtml(item.invoice_no)}" ${disabled}>
        </label>
    ` : '';
    const genericAmountField = !['travelling', 'travelling_allowance'].includes(item.category_code) ? `
        <label class="block space-y-2">
            <span class="wp-label">Amount</span>
            <input class="wp-input" type="number" step="0.01" min="0" data-item-field="amount" value="${escapeHtml(item.amount)}" ${disabled}>
        </label>
    ` : '';

    return `
        <section class="rounded-[28px] border border-white/8 bg-white/[0.03] p-5" data-claim-item data-index="${index}">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p class="wp-label">Item ${index + 1}</p>
                    <p class="wp-helper mt-2">Category and amount fields below map to the legacy claims API.</p>
                </div>
                <div class="flex items-center gap-3">
                    <div class="rounded-full border border-white/8 bg-white/[0.04] px-4 py-2 text-sm font-medium text-white">${money(item.total_amount)}</div>
                    ${canEdit ? `<button type="button" class="wp-btn-ghost" data-claim-remove-item="${index}">Remove</button>` : ''}
                </div>
            </div>

            <div class="mt-5 grid gap-4 md:grid-cols-3">
                <label class="block space-y-2">
                    <span class="wp-label">Category</span>
                    <select class="wp-select" data-item-field="category_code" ${disabled}>
                        ${categoryOptions}
                    </select>
                </label>
                <label class="block space-y-2">
                    <span class="wp-label">Date</span>
                    <input class="wp-input" type="date" data-item-field="item_date" value="${escapeHtml(item.item_date)}" ${disabled}>
                </label>
                ${genericAmountField}
                ${transportReceiptField}
                ${accommodationField}
                ${entertainmentPurposeField}
                ${miscellaneousField}
            </div>

            ${(travellingFields || allowanceFields) ? `<div class="mt-4 space-y-4">${travellingFields}${allowanceFields}</div>` : ''}

            <div class="mt-4 grid gap-4 md:grid-cols-2">
                <label class="block space-y-2">
                    <span class="wp-label">Description</span>
                    <textarea class="wp-textarea" rows="3" data-item-field="description" ${disabled}>${escapeHtml(item.description)}</textarea>
                </label>
                <label class="block space-y-2">
                    <span class="wp-label">Remarks</span>
                    <textarea class="wp-textarea" rows="3" data-item-field="remarks" ${disabled}>${escapeHtml(item.remarks)}</textarea>
                </label>
            </div>
        </section>
    `;
}

function printRowDescription(code, items) {
    if (!items.length) {
        return '-';
    }

    if (code === 'travelling') {
        return items.map((item, index) => {
            const bits = [];
            if (item.distance_km > 0) {
                bits.push(`MILEAGE ${item.distance_km} KM`);
            }
            if (item.from_location || item.to_location) {
                bits.push([item.from_location, item.to_location].filter(Boolean).join(' - '));
            }
            if (item.purpose) {
                bits.push(item.purpose);
            }
            if (item.toll_amount > 0) {
                bits.push('TOLL');
            }
            if (item.parking_amount > 0) {
                bits.push('PARKING');
            }
            return `${index + 1}) ${bits.filter(Boolean).join(' - ') || 'Travelling item'}`;
        }).join('<br>');
    }

    return items.map((item, index) => `${index + 1}) ${escapeHtml(item.description || item.purpose || item.hotel_name || item.receipt_no || item.invoice_no || '-')}`).join('<br>');
}

function printRowAmount(code, items) {
    if (!items.length) {
        return money(0);
    }

    if (code === 'travelling') {
        return items.map((item) => {
            const lines = [];
            if (item.amount > 0) {
                lines.push(`Mileage ${money(item.amount)}`);
            }
            if (item.toll_amount > 0) {
                lines.push(`Toll ${money(item.toll_amount)}`);
            }
            if (item.parking_amount > 0) {
                lines.push(`Parking ${money(item.parking_amount)}`);
            }
            return lines.join('<br>');
        }).join('<br><br>');
    }

    if (code === 'travelling_allowance') {
        return items.map((item) => `${money(item.rate_amount)} x ${item.quantity_value}`).join('<br>');
    }

    return items.map((item) => money(item.total_amount)).join('<br>');
}

export function initClaimsPage() {
    const modal = document.querySelector('#claimModal');
    const rowsRoot = document.querySelector('[data-claims-rows]');
    const monthInput = document.querySelector('[data-claims-month]');
    const searchInput = document.querySelector('[data-claims-search]');
    const refreshButton = document.querySelector('[data-claims-refresh]');
    const newClaimButton = document.querySelector('[data-claims-new]');
    const itemsRoot = document.querySelector('[data-claim-items]');
    const attachmentsRoot = document.querySelector('[data-claim-attachments]');
    const logsRoot = document.querySelector('[data-claim-logs]');
    const feedbackRoot = document.querySelector('[data-claim-feedback]');
    const attachmentInput = document.querySelector('[data-claim-attachment-input]');
    const attachmentUploadButtons = document.querySelectorAll('[data-claim-attachment-upload]');
    const receiptScanButtons = document.querySelectorAll('[data-claim-receipt-scan]');
    const uploadProgressRoot = document.querySelector('[data-claim-upload-progress]');

    if (!modal || !rowsRoot || !monthInput || !searchInput || !itemsRoot || !attachmentsRoot || !logsRoot || !feedbackRoot || !attachmentInput) {
        return;
    }

    const state = {
        me: null,
        categories: [],
        defaultMileageRate: 0,
        activeClaim: defaultClaim(null),
        items: [],
        attachments: [],
        logs: [],
    };

    function showFeedback(message, type = 'info') {
        feedbackRoot.className = 'wp-alert';
        feedbackRoot.classList.remove('hidden', 'wp-alert-info', 'wp-alert-success', 'wp-alert-warning', 'wp-alert-danger');
        feedbackRoot.classList.add(`wp-alert-${type}`);
        feedbackRoot.textContent = message;
    }

    function clearFeedback() {
        feedbackRoot.textContent = '';
        feedbackRoot.className = 'hidden rounded-3xl border px-4 py-3 text-sm';
    }

    function setUploadProgress(message = '') {
        if (!uploadProgressRoot) {
            return;
        }

        uploadProgressRoot.textContent = message;
        uploadProgressRoot.classList.toggle('hidden', !message);
    }

    function syncHeaderToForm() {
        const claim = state.activeClaim;
        setInputValue('[data-claim-id]', claim.id || '');
        setInputValue('[data-claim-header-company]', 'Weststar Engineering');
        setInputValue('[data-claim-header-number]', claim.claim_no || 'Will be generated on save');
        setInputValue('[data-claim-header-name]', claim.employee_name || '');
        setInputValue('[data-claim-header-id]', claim.employee_code || '');
        setInputValue('[data-claim-header-position]', claim.position_title || '');
        setInputValue('[data-claim-header-department]', claim.department || '');
        setInputValue('[data-claim-header-cost-center]', claim.cost_center || '');
        setInputValue('[data-claim-header-month]', claim.claim_month || '');
        setInputValue('[data-claim-header-date]', claim.claim_date || '');
        setInputValue('[data-claim-header-advance]', claim.advance_amount || 0);
        setInputValue('[data-claim-header-remarks]', claim.employee_remarks || '');
        setText('[data-claim-modal-title]', claim.claim_no || 'New Claim');
        setText('[data-claim-modal-copy]', claim.id ? 'Review the claim details, then save, submit, or process the next workflow action.' : 'Create a new claim, save it as draft, then submit when ready.');
    }

    function currentPermissions() {
        return state.activeClaim?.permissions || {};
    }

    function updateDisabledState() {
        const permissions = currentPermissions();
        const canEdit = Boolean(permissions.can_edit);
        const headerSelectors = [
            '[data-claim-header-id]',
            '[data-claim-header-position]',
            '[data-claim-header-department]',
            '[data-claim-header-cost-center]',
            '[data-claim-header-month]',
            '[data-claim-header-date]',
            '[data-claim-header-advance]',
            '[data-claim-header-remarks]',
        ];

        headerSelectors.forEach((selector) => {
            const element = document.querySelector(selector);
            if (element) {
                element.disabled = !canEdit;
            }
        });

        attachmentInput.disabled = !state.activeClaim?.id || !permissions.can_upload;

        const toggleAction = (selector, visible, disabled = false) => {
            document.querySelectorAll(selector).forEach((button) => {
                button.classList.toggle('hidden', !visible);
                button.disabled = disabled;
            });
        };

        toggleAction('[data-claim-save]', canEdit);
        toggleAction('[data-claim-submit]', Boolean(state.activeClaim?.id && permissions.can_submit), !state.items.length);
        toggleAction('[data-claim-approve-manager]', Boolean(permissions.can_manager_review));
        toggleAction('[data-claim-return-manager]', Boolean(permissions.can_manager_review));
        toggleAction('[data-claim-reject-manager]', Boolean(permissions.can_manager_review));
        toggleAction('[data-claim-approve-finance]', Boolean(permissions.can_finance_review));
        toggleAction('[data-claim-mark-paid]', Boolean(permissions.can_mark_paid));
        toggleAction('[data-claim-print]', Boolean(state.activeClaim?.id || state.items.length));
        toggleAction('[data-claim-receipt-scan]', canEdit, !attachmentInput.files?.length);
        toggleAction('[data-claim-attachment-upload]', Boolean(state.activeClaim?.id && permissions.can_upload), !attachmentInput.files?.length);
    }

    function renderAttachments() {
        const canDelete = Boolean(currentPermissions().can_upload);
        attachmentsRoot.innerHTML = state.attachments.length
            ? state.attachments.map((attachment) => attachmentHtml(attachment, canDelete)).join('')
            : '<p class="wp-helper">No attachments uploaded yet.</p>';
    }

    function renderLogs() {
        logsRoot.innerHTML = state.logs.length
            ? state.logs.map((log) => `
                <article class="rounded-3xl border border-white/8 bg-white/[0.03] p-4">
                    <p class="text-sm font-semibold text-white">${escapeHtml(log.action_name || '-')}</p>
                    <p class="wp-helper mt-1">${escapeHtml(log.user_name || log.action_role || '')}</p>
                    <p class="wp-helper mt-1">${escapeHtml(log.created_at || '')}</p>
                    ${log.remarks ? `<p class="wp-helper mt-2">${escapeHtml(log.remarks)}</p>` : ''}
                </article>
            `).join('')
            : '<p class="wp-helper">No activity yet.</p>';
    }

    function renderPrintForm(summary) {
        setText('[data-claim-print-company]', 'Weststar Engineering');
        setText('[data-claim-print-name]', state.activeClaim.employee_name || '');
        setText('[data-claim-print-position]', state.activeClaim.position_title || '');
        setText('[data-claim-print-date]', state.activeClaim.claim_date || '');
        setText('[data-claim-print-grand]', money(summary.grand_total));
        setText('[data-claim-print-advance]', money(summary.advance_amount));
        setText('[data-claim-print-balance]', money(summary.balance_claim));
        setText('[data-claim-print-claimed-by]', state.activeClaim.employee_name || '');

        const printRows = document.querySelector('[data-claim-print-rows]');
        if (!printRows) {
            return;
        }

        printRows.innerHTML = CATEGORY_ORDER.map((category, index) => {
            const items = summary.items.filter((item) => item.category_code === category.code);
            return `
                <tr>
                    <td class="border border-slate-900 px-2 py-4 text-center font-bold">${index + 1}</td>
                    <td class="border border-slate-900 px-2 py-4 font-bold">${category.label}</td>
                    <td class="border border-slate-900 px-2 py-4">${printRowDescription(category.code, items)}</td>
                    <td class="border border-slate-900 px-2 py-4 text-right">${printRowAmount(category.code, items)}</td>
                    <td class="border border-slate-900 px-2 py-4 text-right">${money(summary.totals[category.code])}</td>
                </tr>
            `;
        }).join('');
    }

    function renderSummary() {
        const summary = computeSummary(state.items, state.activeClaim.advance_amount);
        state.items = summary.items;
        state.activeClaim = {
            ...state.activeClaim,
            grand_total: summary.grand_total,
            balance_claim: summary.balance_claim,
        };

        setText('[data-claim-summary-travel]', money(summary.totals.travelling));
        setText('[data-claim-summary-transportation]', money(summary.totals.transportation));
        setText('[data-claim-summary-accommodation]', money(summary.totals.accommodation));
        setText('[data-claim-summary-travelling-allowance]', money(summary.totals.travelling_allowance));
        setText('[data-claim-summary-entertainment]', money(summary.totals.entertainment));
        setText('[data-claim-summary-miscellaneous]', money(summary.totals.miscellaneous));
        setText('[data-claim-summary-advance]', money(summary.advance_amount));
        setText('[data-claim-summary-balance]', money(summary.balance_claim));
        setText('[data-claim-summary-grand]', money(summary.grand_total));

        renderPrintForm(summary);
        updateDisabledState();
    }

    function renderItemEditors() {
        const canEdit = Boolean(currentPermissions().can_edit);
        itemsRoot.innerHTML = state.items.length
            ? state.items.map((item, index) => itemEditorHtml(item, index, state.categories, canEdit)).join('')
            : '<div class="wp-empty-state"><p class="wp-section-title">No claim items yet</p><p class="wp-helper">Add at least one item before submitting the claim.</p></div>';

        renderSummary();
    }

    function hydrateFromHeader() {
        state.activeClaim = {
            ...state.activeClaim,
            employee_code: document.querySelector('[data-claim-header-id]')?.value || '',
            position_title: document.querySelector('[data-claim-header-position]')?.value || '',
            department: document.querySelector('[data-claim-header-department]')?.value || '',
            cost_center: document.querySelector('[data-claim-header-cost-center]')?.value || '',
            claim_month: document.querySelector('[data-claim-header-month]')?.value || '',
            claim_date: document.querySelector('[data-claim-header-date]')?.value || '',
            advance_amount: numberValue(document.querySelector('[data-claim-header-advance]')?.value || 0),
            employee_remarks: document.querySelector('[data-claim-header-remarks]')?.value || '',
        };
    }

    function claimPayload() {
        hydrateFromHeader();
        const summary = computeSummary(state.items, state.activeClaim.advance_amount);
        state.items = summary.items;

        return {
            claim_id: state.activeClaim.id || 0,
            employee_code: state.activeClaim.employee_code || '',
            position_title: state.activeClaim.position_title || '',
            department: state.activeClaim.department || '',
            cost_center: state.activeClaim.cost_center || '',
            claim_month: state.activeClaim.claim_month || '',
            claim_date: state.activeClaim.claim_date || '',
            advance_amount: summary.advance_amount,
            employee_remarks: state.activeClaim.employee_remarks || '',
            items: state.items.map((item) => ({
                category_code: item.category_code,
                item_date: item.item_date,
                from_location: item.from_location,
                to_location: item.to_location,
                purpose: item.purpose,
                receipt_no: item.receipt_no,
                invoice_no: item.invoice_no,
                hotel_name: item.hotel_name,
                description: item.description,
                distance_km: item.distance_km,
                mileage_rate: item.mileage_rate,
                toll_amount: item.toll_amount,
                parking_amount: item.parking_amount,
                rate_amount: item.rate_amount,
                quantity_value: item.quantity_value,
                amount: item.amount,
                remarks: item.remarks,
            })),
        };
    }

    function applyClaimDetail(payload) {
        const claim = payload?.claim || defaultClaim(state.me);
        state.activeClaim = {
            ...state.activeClaim,
            ...claim,
        };
        state.categories = payload?.categories?.length ? payload.categories : state.categories;
        state.defaultMileageRate = numberValue(payload?.default_mileage_rate, state.defaultMileageRate);
        state.items = (payload?.items || []).map((item) => normalizeItem(item, state.defaultMileageRate));
        state.attachments = payload?.attachments || [];
        state.logs = payload?.logs || [];

        if (!state.items.length && currentPermissions().can_edit) {
            state.items = [blankItem('travelling', state.defaultMileageRate)];
        }

        syncHeaderToForm();
        renderItemEditors();
        renderAttachments();
        renderLogs();
        setText('[data-claim-mileage-rate]', `Default mileage rate ${money(state.defaultMileageRate)}`);
        activateTab('header');
        openModal(modal);
    }

    function resetToNewClaim() {
        clearFeedback();
        state.activeClaim = defaultClaim(state.me);
        state.activeClaim.permissions.can_edit = true;
        state.activeClaim.permissions.can_submit = false;
        state.activeClaim.permissions.can_upload = false;
        state.items = [blankItem('travelling', state.defaultMileageRate)];
        state.attachments = [];
        state.logs = [];
        syncHeaderToForm();
        renderItemEditors();
        renderAttachments();
        renderLogs();
        setText('[data-claim-mileage-rate]', `Default mileage rate ${money(state.defaultMileageRate)}`);
        activateTab('header');
    }

    async function loadClaims() {
        try {
            const payload = await fetchClaimsList({
                month: monthInput.value || '',
                q: searchInput.value || '',
            });

            state.me = payload?.me || state.me;
            state.categories = payload?.categories || state.categories;
            state.defaultMileageRate = numberValue(payload?.default_mileage_rate, state.defaultMileageRate);

            setText('[data-claims-total]', String(payload?.summary?.total ?? '0'));
            setText('[data-claims-grand-total]', money(payload?.summary?.grand_total));

            rowsRoot.innerHTML = (payload?.claims || []).map((claim) => `
                <tr>
                    <td data-label="Claim No">
                        <div class="font-semibold text-white">${escapeHtml(claim.claim_no)}</div>
                        <div class="mt-1 text-xs text-ink-400">${claim.item_count} item / ${claim.attachment_count} files</div>
                    </td>
                    <td data-label="Employee">${escapeHtml(claim.employee_name)}</td>
                    <td data-label="Month">${escapeHtml(claim.claim_month)}</td>
                    <td data-label="Total" class="font-semibold text-white">${money(claim.grand_total)}</td>
                    <td data-label="Updated">${escapeHtml(claim.updated_at || '-')}</td>
                    <td data-label="Action"><button type="button" class="wp-btn-secondary" data-claim-open="${claim.id}">${claim.permissions?.can_edit ? 'Edit' : 'Open'}</button></td>
                </tr>
            `).join('') || '<tr><td colspan="6" class="py-8 text-center text-sm text-ink-400">No claims found for the selected filter.</td></tr>';

            if (!state.activeClaim.id) {
                resetToNewClaim();
            }
        } catch (error) {
            console.warn('claims api:', mapApiError(error));
            showFeedback(mapApiError(error), 'danger');
        }
    }

    async function loadClaimDetail(id) {
        try {
            clearFeedback();
            const payload = await fetchClaimDetail(id);
            applyClaimDetail(payload);
        } catch (error) {
            showFeedback(mapApiError(error), 'danger');
            showGlobalFeedback(mapApiError(error), 'danger');
        }
    }

    async function saveCurrentClaim() {
        try {
            clearFeedback();
            const payload = await saveClaimDraft(claimPayload());
            showFeedback('Claim draft saved.', 'success');
            await loadClaims();
            await loadClaimDetail(payload?.claim?.id);
        } catch (error) {
            showFeedback(mapApiError(error), 'danger');
        }
    }

    async function submitCurrentClaim() {
        if (!state.activeClaim.id) {
            await saveCurrentClaim();
        }

        if (!state.activeClaim.id) {
            return;
        }

        try {
            clearFeedback();
            await submitClaim(state.activeClaim.id);
            showFeedback('Claim submitted for approval.', 'success');
            await loadClaims();
            await loadClaimDetail(state.activeClaim.id);
        } catch (error) {
            showFeedback(mapApiError(error), 'danger');
        }
    }

    async function runAction(action, label) {
        if (!state.activeClaim.id) {
            return;
        }

        try {
            clearFeedback();
            const remarks = promptRemarks(label);
            await runClaimAction({
                claim_id: state.activeClaim.id,
                action,
                remarks,
            });
            showFeedback(`${label} completed.`, 'success');
            await loadClaims();
            await loadClaimDetail(state.activeClaim.id);
        } catch (error) {
            showFeedback(mapApiError(error), 'danger');
        }
    }

    async function uploadAttachmentsNow() {
        if (!state.activeClaim.id) {
            showFeedback('Save the claim draft before uploading attachments.', 'warning');
            return;
        }

        if (!attachmentInput.files?.length) {
            showFeedback('Choose at least one file to upload.', 'warning');
            return;
        }

        try {
            clearFeedback();
            setUploadProgress('Uploading attachments... 0%');
            await uploadClaimAttachments(state.activeClaim.id, attachmentInput.files, (progressEvent) => {
                const total = Number(progressEvent?.total || 0);
                const loaded = Number(progressEvent?.loaded || 0);
                const percent = total > 0 ? Math.min(100, Math.round((loaded / total) * 100)) : 0;
                setUploadProgress(`Uploading attachments... ${percent}%`);
            });
            attachmentInput.value = '';
            showFeedback('Attachments uploaded.', 'success');
            showGlobalFeedback('Claim attachments uploaded successfully.', 'success');
            await loadClaimDetail(state.activeClaim.id);
        } catch (error) {
            showFeedback(mapApiError(error), 'danger');
            showGlobalFeedback(mapApiError(error), 'danger');
        } finally {
            setUploadProgress('');
        }
    }

    function applyReceiptExtraction(receipt) {
        if (!receipt || typeof receipt !== 'object') {
            return;
        }

        const categoryCode = receipt.suggested_category_code || 'miscellaneous';
        const currentItem = state.items.length === 1 ? state.items[0] : null;
        const replaceFirstBlankItem = currentItem
            && !currentItem.description
            && !currentItem.receipt_no
            && !currentItem.invoice_no
            && Number(currentItem.amount || 0) === 0
            && Number(currentItem.total_amount || 0) === 0;

        const nextItem = normalizeItem({
            ...(replaceFirstBlankItem ? currentItem : blankItem(categoryCode, state.defaultMileageRate)),
            category_code: categoryCode,
            item_date: receipt.item_date || receipt.claim_date || state.activeClaim.claim_date,
            purpose: receipt.purpose || '',
            receipt_no: receipt.receipt_no || '',
            invoice_no: receipt.invoice_no || '',
            hotel_name: receipt.hotel_name || receipt.merchant_name || '',
            description: receipt.description || receipt.merchant_name || '',
            amount: numberValue(receipt.amount),
            remarks: receipt.remarks || '',
        }, state.defaultMileageRate);

        if (receipt.claim_date) {
            state.activeClaim.claim_date = receipt.claim_date;
        }

        if (receipt.claim_month) {
            state.activeClaim.claim_month = receipt.claim_month;
        }

        if (replaceFirstBlankItem) {
            state.items[0] = nextItem;
        } else {
            state.items.unshift(nextItem);
        }

        syncHeaderToForm();
        renderItemEditors();
        activateTab('items');
    }

    async function scanReceiptNow() {
        const file = attachmentInput.files?.[0];
        if (!file) {
            showFeedback('Choose one receipt file to scan first.', 'warning');
            return;
        }

        try {
            clearFeedback();
            setUploadProgress('Scanning receipt with Anthropic... 0%');
            const payload = await extractClaimReceipt(file, (progressEvent) => {
                const total = Number(progressEvent?.total || 0);
                const loaded = Number(progressEvent?.loaded || 0);
                const percent = total > 0 ? Math.min(100, Math.round((loaded / total) * 100)) : 0;
                setUploadProgress(`Scanning receipt with Anthropic... ${percent}%`);
            });

            applyReceiptExtraction(payload?.receipt);

            const warnings = Array.isArray(payload?.receipt?.warnings) && payload.receipt.warnings.length
                ? ` Warnings: ${payload.receipt.warnings.join(' ')}`
                : '';

            showFeedback(`Receipt scanned and claim form updated.${warnings}`, 'success');
            showGlobalFeedback('Receipt extracted into claim form.', 'success');
        } catch (error) {
            showFeedback(mapApiError(error), 'danger');
            showGlobalFeedback(mapApiError(error), 'danger');
        } finally {
            setUploadProgress('');
        }
    }

    async function deleteAttachmentNow(attachmentId) {
        try {
            clearFeedback();
            await deleteClaimAttachment(state.activeClaim.id, attachmentId);
            showFeedback('Attachment removed.', 'success');
            await loadClaimDetail(state.activeClaim.id);
        } catch (error) {
            showFeedback(mapApiError(error), 'danger');
        }
    }

    newClaimButton?.addEventListener('click', () => {
        resetToNewClaim();
        openModal(modal);
    });

    refreshButton?.addEventListener('click', loadClaims);
    monthInput.addEventListener('change', loadClaims);
    searchInput.addEventListener('input', loadClaims);

    rowsRoot.addEventListener('click', (event) => {
        const button = event.target.closest('[data-claim-open]');
        if (!button) {
            return;
        }

        const id = button.getAttribute('data-claim-open');
        if (id) {
            loadClaimDetail(id);
        }
    });

    document.querySelectorAll('[data-claim-save]').forEach((button) => {
        button.addEventListener('click', saveCurrentClaim);
    });

    document.querySelectorAll('[data-claim-submit]').forEach((button) => {
        button.addEventListener('click', submitCurrentClaim);
    });

    document.querySelectorAll('[data-claim-approve-manager]').forEach((button) => {
        button.addEventListener('click', () => runAction('approve_manager', 'manager approval'));
    });

    document.querySelectorAll('[data-claim-return-manager]').forEach((button) => {
        button.addEventListener('click', () => runAction('return_manager', 'return for amendment'));
    });

    document.querySelectorAll('[data-claim-reject-manager]').forEach((button) => {
        button.addEventListener('click', () => runAction('reject_manager', 'manager rejection'));
    });

    document.querySelectorAll('[data-claim-approve-finance]').forEach((button) => {
        button.addEventListener('click', () => runAction('approve_finance', 'finance approval'));
    });

    document.querySelectorAll('[data-claim-mark-paid]').forEach((button) => {
        button.addEventListener('click', () => runAction('mark_paid', 'mark paid'));
    });

    document.querySelectorAll('[data-claim-print]').forEach((button) => {
        button.addEventListener('click', () => {
            activateTab('form');
            window.print();
        });
    });

    document.querySelector('[data-claim-add-item]')?.addEventListener('click', () => {
        state.items.push(blankItem('travelling', state.defaultMileageRate));
        renderItemEditors();
    });

    itemsRoot.addEventListener('click', (event) => {
        const removeButton = event.target.closest('[data-claim-remove-item]');
        if (!removeButton) {
            return;
        }

        const index = Number(removeButton.getAttribute('data-claim-remove-item'));
        state.items.splice(index, 1);
        renderItemEditors();
    });

    itemsRoot.addEventListener('input', (event) => {
        const field = event.target.getAttribute('data-item-field');
        const row = event.target.closest('[data-claim-item]');
        if (!field || !row) {
            return;
        }

        const index = Number(row.dataset.index);
        if (!state.items[index]) {
            return;
        }

        state.items[index] = {
            ...state.items[index],
            [field]: event.target.value,
        };

        renderSummary();
    });

    itemsRoot.addEventListener('change', (event) => {
        const field = event.target.getAttribute('data-item-field');
        const row = event.target.closest('[data-claim-item]');
        if (!field || !row) {
            return;
        }

        const index = Number(row.dataset.index);
        if (!state.items[index]) {
            return;
        }

        state.items[index] = {
            ...state.items[index],
            [field]: event.target.value,
        };

        if (field === 'category_code') {
            state.items[index] = blankItem(event.target.value, state.defaultMileageRate);
        }

        renderItemEditors();
    });

    document.querySelectorAll([
        '[data-claim-header-id]',
        '[data-claim-header-position]',
        '[data-claim-header-department]',
        '[data-claim-header-cost-center]',
        '[data-claim-header-month]',
        '[data-claim-header-date]',
        '[data-claim-header-advance]',
        '[data-claim-header-remarks]',
    ].join(',')).forEach((input) => {
        input.addEventListener('input', () => {
            hydrateFromHeader();
            renderSummary();
        });
    });

    attachmentUploadButtons.forEach((button) => {
        button.addEventListener('click', uploadAttachmentsNow);
    });

    receiptScanButtons.forEach((button) => {
        button.addEventListener('click', scanReceiptNow);
    });

    attachmentInput.addEventListener('change', updateDisabledState);

    attachmentsRoot.addEventListener('click', (event) => {
        const button = event.target.closest('[data-claim-attachment-delete]');
        if (!button) {
            return;
        }

        deleteAttachmentNow(button.getAttribute('data-claim-attachment-delete'));
    });

    loadClaims();
}
