import {
    fetchAttendanceAdminList,
    fetchAttendanceList,
    fetchAttendanceStatus,
    postAttendanceEvent,
    saveAttendanceAdminEntry,
    saveAttendanceEntry,
} from '../api/attendance';
import { mapApiError } from '../api/client';
import { showGlobalFeedback } from '../ui/feedback';

function escapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#39;');
}

function statusVariant(status) {
    if (status === 'On time' || status === 'Present') return 'success';
    if (status === 'Late') return 'warning';
    if (status === 'Absent') return 'danger';
    if (status === 'Leave' || status === 'Weekend') return 'info';
    return 'neutral';
}

function badgeHtml(status) {
    const variant = statusVariant(status);
    return `<span class="wp-badge wp-badge-${variant}">${escapeHtml(status || '-')}</span>`;
}

function eventLabel(event) {
    switch (event) {
        case 'check_in': return 'Check In';
        case 'break_out': return 'Start Break';
        case 'break_in': return 'End Break';
        case 'check_out': return 'Check Out';
        default: return 'Run Next Action';
    }
}

function isoDate(date) {
    return date.toISOString().slice(0, 10);
}

function shiftDate(dateString, diff) {
    const current = new Date(`${dateString}T00:00:00`);
    current.setDate(current.getDate() + diff);
    return isoDate(current);
}

function openModal(id) {
    document.getElementById(id)?.classList.add('is-open');
}

function closeModal(id) {
    document.getElementById(id)?.classList.remove('is-open');
}

function downloadCsv(filename, headers, rows) {
    const csvRows = [
        headers.join(','),
        ...rows.map((row) => row.map((value) => {
            const text = String(value ?? '');
            return `"${text.replaceAll('"', '""')}"`;
        }).join(',')),
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

export function initAttendancePage() {
    const tableBody = document.querySelector('[data-attendance-rows]');
    const dateInput = document.querySelector('[data-attendance-date-input]');
    const searchInput = document.querySelector('[data-attendance-search]');
    const filterInput = document.querySelector('[data-attendance-filter]');
    const sortInput = document.querySelector('[data-attendance-sort]');
    const nextBadge = document.querySelector('[data-attendance-next-badge]');
    const statusCopy = document.querySelector('[data-attendance-status-copy]');
    const eventButton = document.querySelector('[data-attendance-event]');
    const adminPanel = document.querySelector('[data-attendance-admin-panel]');
    const adminRowsRoot = document.querySelector('[data-attendance-admin-rows]');
    const adminDateInput = document.querySelector('[data-attendance-admin-date]');

    if (!tableBody || !dateInput || !filterInput || !sortInput || !nextBadge || !statusCopy || !eventButton) {
        return;
    }

    const state = {
        mode: 'day',
        rangeKey: 'today',
        selectedDate: dateInput.value || document.documentElement.dataset.attendanceDate || isoDate(new Date()),
        rangeStart: null,
        rangeEnd: null,
        rows: [],
        currentStatus: null,
        adminEnabled: true,
        adminRows: [],
    };

    function currentParams() {
        const params = {
            filter: filterInput.value || 'all',
            sort: sortInput.value || 'desc',
        };

        if (state.mode === 'range' && state.rangeStart && state.rangeEnd) {
            params.start = state.rangeStart;
            params.end = state.rangeEnd;
        } else {
            params.start = state.selectedDate;
            params.end = state.selectedDate;
        }

        return params;
    }

    function renderRows() {
        const term = (searchInput?.value || '').trim().toLowerCase();
        const rows = term
            ? state.rows.filter((row) => {
                return [
                    row.work_date,
                    row.first_check_in,
                    row.last_check_out,
                    row.status_label,
                ].some((value) => String(value || '').toLowerCase().includes(term));
            })
            : state.rows;

        tableBody.innerHTML = rows.length ? rows.map((row) => `
            <tr>
                <td data-label="Date">${escapeHtml(row.work_date || '-')}</td>
                <td data-label="Check In">${escapeHtml(row.first_check_in || '-')}</td>
                <td data-label="Break">${escapeHtml(row.break_hm || '-')}</td>
                <td data-label="Check Out">${escapeHtml(row.last_check_out || '-')}</td>
                <td data-label="Total">${escapeHtml(row.work_hm || '-')}</td>
                <td data-label="Status">${badgeHtml(row.status_label || '-')}</td>
                <td data-label="Action"><button type="button" class="wp-btn-secondary" data-attendance-edit data-date="${escapeHtml(row.work_date)}">Edit</button></td>
            </tr>
        `).join('') : '<tr><td colspan="7" class="py-8 text-center text-sm text-ink-400">No attendance records found for the selected filter.</td></tr>';
    }

    function updateRangeTabs(activeKey) {
        document.querySelectorAll('[data-attendance-range]').forEach((button) => {
            button.classList.toggle('wp-tab-active', button.getAttribute('data-attendance-range') === activeKey);
        });
    }

    function updateStatusPanel() {
        const status = state.currentStatus;
        if (!status) {
            nextBadge.textContent = 'Unavailable';
            statusCopy.textContent = 'Unable to determine current attendance state.';
            eventButton.textContent = 'Run Next Action';
            return;
        }

        nextBadge.textContent = eventLabel(status.next_event);
        statusCopy.textContent = status.checked_in
            ? `Currently checked in. Next recommended action: ${eventLabel(status.next_event)}.`
            : `Currently not checked in. Next recommended action: ${eventLabel(status.next_event)}.`;
        eventButton.textContent = eventLabel(status.next_event);
    }

    function populateEditModal(date) {
        const row = state.rows.find((item) => item.work_date === date) || null;
        document.querySelector('[data-attendance-edit-date]')?.setAttribute('value', date || state.selectedDate);
        const dateField = document.querySelector('[data-attendance-edit-date]');
        if (dateField) dateField.value = date || state.selectedDate;
        const checkinField = document.querySelector('[data-attendance-edit-checkin]');
        const checkoutField = document.querySelector('[data-attendance-edit-checkout]');
        const breakField = document.querySelector('[data-attendance-edit-break]');

        if (checkinField) checkinField.value = row?.first_check_in ? row.first_check_in.replace(/^(\d{2}):(\d{2})\s([ap]m)$/i, (_, hh, mm, meridian) => {
            let hour = Number(hh);
            if (meridian.toLowerCase() === 'pm' && hour !== 12) hour += 12;
            if (meridian.toLowerCase() === 'am' && hour === 12) hour = 0;
            return `${String(hour).padStart(2, '0')}:${mm}`;
        }) : '';
        if (checkoutField) checkoutField.value = row?.last_check_out ? row.last_check_out.replace(/^(\d{2}):(\d{2})\s([ap]m)$/i, (_, hh, mm, meridian) => {
            let hour = Number(hh);
            if (meridian.toLowerCase() === 'pm' && hour !== 12) hour += 12;
            if (meridian.toLowerCase() === 'am' && hour === 12) hour = 0;
            return `${String(hour).padStart(2, '0')}:${mm}`;
        }) : '';
        if (breakField) {
            const breakText = row?.break_hm || '00:00';
            const [hours, minutes] = breakText.split(':').map((value) => Number(value || 0));
            breakField.value = (hours * 60) + minutes;
        }
    }

    async function loadAttendance() {
        try {
            const payload = await fetchAttendanceList(currentParams());
            state.rows = Array.isArray(payload?.rows) ? payload.rows : [];
            renderRows();
        } catch (error) {
            console.warn('attendance api:', mapApiError(error));
            tableBody.innerHTML = `<tr><td colspan="7" class="py-8 text-center text-sm text-danger-400">${escapeHtml(mapApiError(error))}</td></tr>`;
        }
    }

    async function loadStatus() {
        try {
            state.currentStatus = await fetchAttendanceStatus();
        } catch (error) {
            console.warn('attendance status api:', mapApiError(error));
            state.currentStatus = null;
        }

        updateStatusPanel();
    }

    function formatSecondsToHm(seconds) {
        const total = Number(seconds || 0);
        const hours = Math.floor(total / 3600);
        const minutes = Math.floor((total % 3600) / 60);
        return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}`;
    }

    function renderAdminRows() {
        if (!adminRowsRoot) {
            return;
        }

        adminRowsRoot.innerHTML = state.adminRows.length ? state.adminRows.map((row) => `
            <tr>
                <td data-label="Name">${escapeHtml(row.name || '-')}</td>
                <td data-label="Email">${escapeHtml(row.email || '-')}</td>
                <td data-label="Check In">${row.first_check_in ? escapeHtml(String(row.first_check_in).slice(11, 16)) : '-'}</td>
                <td data-label="Check Out">${row.last_check_out ? escapeHtml(String(row.last_check_out).slice(11, 16)) : '-'}</td>
                <td data-label="Work">${formatSecondsToHm(row.total_work_seconds || 0)}</td>
                <td data-label="Break">${formatSecondsToHm(row.total_break_seconds || 0)}</td>
                <td data-label="Status">${badgeHtml(row.status ? String(row.status).replace('_', ' ') : '-')}</td>
                <td data-label="Action"><button type="button" class="wp-btn-secondary" data-attendance-admin-edit="${row.user_id}">Edit</button></td>
            </tr>
        `).join('') : '<tr><td colspan="8" class="py-8 text-center text-sm text-ink-400">No admin rows found for this date.</td></tr>';
    }

    function populateAdminEditModal(userId) {
        const row = state.adminRows.find((item) => Number(item.user_id) === Number(userId));
        if (!row) {
            return;
        }

        const userField = document.querySelector('[data-attendance-admin-user]');
        const dateField = document.querySelector('[data-attendance-admin-edit-date]');
        const statusField = document.querySelector('[data-attendance-admin-status]');
        const checkInField = document.querySelector('[data-attendance-admin-checkin]');
        const checkOutField = document.querySelector('[data-attendance-admin-checkout]');
        const breakField = document.querySelector('[data-attendance-admin-break]');

        if (userField) userField.value = String(row.user_id);
        if (dateField) dateField.value = row.work_date || adminDateInput?.value || state.selectedDate;
        if (statusField) statusField.value = row.status || 'on_time';
        if (checkInField) checkInField.value = row.first_check_in ? String(row.first_check_in).slice(11, 16) : '';
        if (checkOutField) checkOutField.value = row.last_check_out ? String(row.last_check_out).slice(11, 16) : '';
        if (breakField) breakField.value = Math.round(Number(row.total_break_seconds || 0) / 60);
    }

    async function loadAdminRows() {
        if (!adminRowsRoot || !adminDateInput) {
            return;
        }

        try {
            const payload = await fetchAttendanceAdminList(adminDateInput.value || state.selectedDate);
            state.adminEnabled = true;
            state.adminRows = Array.isArray(payload?.rows) ? payload.rows : [];
            adminPanel?.classList.remove('hidden');
            renderAdminRows();
        } catch (error) {
            const message = mapApiError(error);
            if (String(message).toLowerCase().includes('forbidden')) {
                state.adminEnabled = false;
                adminPanel?.classList.add('hidden');
                return;
            }

            console.warn('attendance admin api:', message);
            adminRowsRoot.innerHTML = `<tr><td colspan="8" class="py-8 text-center text-sm text-danger-400">${escapeHtml(message)}</td></tr>`;
        }
    }

    async function refreshAll() {
        await Promise.all([loadAttendance(), loadStatus(), loadAdminRows()]);
    }

    function setDayMode(date) {
        state.mode = 'day';
        state.rangeKey = 'today';
        state.selectedDate = date;
        dateInput.value = date;
        updateRangeTabs('today');
        refreshAll();
    }

    function setPresetRange(key) {
        const today = isoDate(new Date());
        state.mode = 'range';
        state.rangeKey = key;
        state.rangeEnd = today;

        if (key === '7d') {
            state.rangeStart = shiftDate(today, -6);
        } else if (key === '30d') {
            state.rangeStart = shiftDate(today, -29);
        } else {
            state.mode = 'day';
            state.selectedDate = today;
            dateInput.value = today;
        }

        updateRangeTabs(key);
        refreshAll();
    }

    document.querySelector('[data-attendance-prev]')?.addEventListener('click', () => {
        setDayMode(shiftDate(dateInput.value || state.selectedDate, -1));
    });

    document.querySelector('[data-attendance-next]')?.addEventListener('click', () => {
        setDayMode(shiftDate(dateInput.value || state.selectedDate, 1));
    });

    document.querySelector('[data-attendance-today]')?.addEventListener('click', () => {
        setDayMode(isoDate(new Date()));
    });

    dateInput.addEventListener('change', () => {
        setDayMode(dateInput.value || state.selectedDate);
    });

    document.querySelectorAll('[data-attendance-range]').forEach((button) => {
        button.addEventListener('click', () => {
            const key = button.getAttribute('data-attendance-range');
            if (key === 'today') {
                setDayMode(isoDate(new Date()));
                return;
            }

            setPresetRange(key);
        });
    });

    document.querySelectorAll('[data-attendance-range-open]').forEach((button) => {
        button.addEventListener('click', () => {
            document.querySelector('[data-attendance-range-start]')?.setAttribute('value', state.rangeStart || shiftDate(state.selectedDate, -6));
            document.querySelector('[data-attendance-range-end]')?.setAttribute('value', state.rangeEnd || state.selectedDate);
            const startField = document.querySelector('[data-attendance-range-start]');
            const endField = document.querySelector('[data-attendance-range-end]');
            if (startField) startField.value = state.rangeStart || shiftDate(state.selectedDate, -6);
            if (endField) endField.value = state.rangeEnd || state.selectedDate;
            openModal('attendanceRangeModal');
        });
    });

    document.querySelector('[data-attendance-range-apply]')?.addEventListener('click', () => {
        const start = document.querySelector('[data-attendance-range-start]')?.value || '';
        const end = document.querySelector('[data-attendance-range-end]')?.value || '';
        if (!start || !end) {
            return;
        }

        state.mode = 'range';
        state.rangeKey = 'custom';
        state.rangeStart = start;
        state.rangeEnd = end;
        updateRangeTabs('custom');
        closeModal('attendanceRangeModal');
        refreshAll();
    });

    document.querySelector('[data-attendance-refresh]')?.addEventListener('click', refreshAll);

    eventButton.addEventListener('click', async () => {
        try {
            eventButton.disabled = true;
            const event = state.currentStatus?.next_event || 'check_in';
            await postAttendanceEvent({ event });
            await refreshAll();
            showGlobalFeedback(`${eventLabel(event)} recorded successfully.`, 'success');
        } catch (error) {
            showGlobalFeedback(mapApiError(error), 'danger');
        } finally {
            eventButton.disabled = false;
        }
    });

    document.querySelector('[data-attendance-edit-open]')?.addEventListener('click', () => {
        populateEditModal(state.selectedDate);
        openModal('attendanceEditModal');
    });

    tableBody.addEventListener('click', (event) => {
        const button = event.target.closest('[data-attendance-edit]');
        if (!button) {
            return;
        }

        populateEditModal(button.getAttribute('data-date') || state.selectedDate);
        openModal('attendanceEditModal');
    });

    document.querySelector('[data-attendance-edit-save]')?.addEventListener('click', async () => {
        const date = document.querySelector('[data-attendance-edit-date]')?.value || '';
        const checkIn = document.querySelector('[data-attendance-edit-checkin]')?.value || '';
        const checkOut = document.querySelector('[data-attendance-edit-checkout]')?.value || '';
        const breakMinutes = document.querySelector('[data-attendance-edit-break]')?.value || '0';

        try {
            await saveAttendanceEntry({
                date,
                check_in: checkIn,
                check_out: checkOut,
                break_minutes: Number(breakMinutes || 0),
            });
            closeModal('attendanceEditModal');
            await refreshAll();
            showGlobalFeedback('Attendance entry updated successfully.', 'success');
        } catch (error) {
            showGlobalFeedback(mapApiError(error), 'danger');
        }
    });

    adminRowsRoot?.addEventListener('click', (event) => {
        const button = event.target.closest('[data-attendance-admin-edit]');
        if (!button) {
            return;
        }

        populateAdminEditModal(button.getAttribute('data-attendance-admin-edit'));
        openModal('attendanceAdminEditModal');
    });

    document.querySelector('[data-attendance-admin-refresh]')?.addEventListener('click', loadAdminRows);
    adminDateInput?.addEventListener('change', loadAdminRows);

    document.querySelector('[data-attendance-admin-save]')?.addEventListener('click', async () => {
        try {
            await saveAttendanceAdminEntry({
                user_id: Number(document.querySelector('[data-attendance-admin-user]')?.value || 0),
                date: document.querySelector('[data-attendance-admin-edit-date]')?.value || '',
                status: document.querySelector('[data-attendance-admin-status]')?.value || 'on_time',
                check_in: document.querySelector('[data-attendance-admin-checkin]')?.value || '',
                check_out: document.querySelector('[data-attendance-admin-checkout]')?.value || '',
                break_minutes: Number(document.querySelector('[data-attendance-admin-break]')?.value || 0),
            });
            closeModal('attendanceAdminEditModal');
            await Promise.all([loadAdminRows(), loadAttendance()]);
            showGlobalFeedback('Daily attendance updated successfully.', 'success');
        } catch (error) {
            showGlobalFeedback(mapApiError(error), 'danger');
        }
    });

    document.querySelector('[data-attendance-admin-export]')?.addEventListener('click', () => {
        const date = adminDateInput?.value || state.selectedDate;
        downloadCsv(`attendance-admin-${date}.csv`, [
            'Name',
            'Email',
            'Check In',
            'Check Out',
            'Work',
            'Break',
            'Status',
        ], state.adminRows.map((row) => [
            row.name || '',
            row.email || '',
            row.first_check_in || '',
            row.last_check_out || '',
            formatSecondsToHm(row.total_work_seconds || 0),
            formatSecondsToHm(row.total_break_seconds || 0),
            row.status || '',
        ]));
    });

    document.querySelector('[data-attendance-export]')?.addEventListener('click', () => {
        const suffix = state.mode === 'range'
            ? `${state.rangeStart || state.selectedDate}-to-${state.rangeEnd || state.selectedDate}`
            : state.selectedDate;
        downloadCsv(`attendance-${suffix}.csv`, [
            'Date',
            'Check In',
            'Break',
            'Check Out',
            'Total',
            'Status',
        ], state.rows.map((row) => [
            row.work_date || '',
            row.first_check_in || '',
            row.break_hm || '',
            row.last_check_out || '',
            row.work_hm || '',
            row.status_label || '',
        ]));
    });

    searchInput?.addEventListener('input', renderRows);
    filterInput.addEventListener('change', loadAttendance);
    sortInput.addEventListener('change', loadAttendance);

    refreshAll();
}
