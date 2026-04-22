import { fetchAttendanceReportList, fetchReportSummary } from '../api/reports';
import { mapApiError } from '../api/client';
import { alertHtml, tableMessageRow } from '../ui/states';

function formatDuration(seconds) {
    const total = Number(seconds || 0);
    return `${Math.floor(total / 3600)}h ${Math.floor((total % 3600) / 60)}m`;
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

export function initReportsPage() {
    const summaryRoot = document.querySelector('[data-report-summary]');
    const rowsRoot = document.querySelector('[data-report-rows]');
    const startInput = document.querySelector('[data-report-start]');
    const endInput = document.querySelector('[data-report-end]');
    if (!summaryRoot || !rowsRoot || !startInput || !endInput) return;
    const state = { rows: [] };

    async function loadReports() {
        const params = { start: startInput.value || '', end: endInput.value || '' };
        try {
            const [summaryPayload, rowsPayload] = await Promise.all([
                fetchReportSummary(params),
                fetchAttendanceReportList({ date: endInput.value || startInput.value || '' }),
            ]);

            const insights = summaryPayload?.insights || {};
            summaryRoot.innerHTML = `
                <article class="wp-panel p-6"><p class="wp-label">On-time Rate</p><p class="mt-4 text-3xl font-semibold tracking-tight text-white">${Math.round((insights.on_time_rate || 0) * 100)}%</p></article>
                <article class="wp-panel p-6"><p class="wp-label">Avg Break</p><p class="mt-4 text-3xl font-semibold tracking-tight text-white">${formatDuration(insights.avg_break_seconds || 0)}</p></article>
                <article class="wp-panel p-6"><p class="wp-label">Working Days</p><p class="mt-4 text-3xl font-semibold tracking-tight text-white">${insights.working_days || 0}</p></article>
            `;

            const rows = rowsPayload?.rows || [];
            state.rows = rows;
            rowsRoot.innerHTML = rows.length ? rows.map((row) => `
                <tr>
                    <td data-label="Employee">${row.name || '-'}</td>
                    <td data-label="Present">${row.first_check_in ? '1' : '0'}</td>
                    <td data-label="Late">${row.status === 'late' ? '1' : '0'}</td>
                    <td data-label="Leave">${row.status === 'leave' ? '1' : '0'}</td>
                    <td data-label="Hours">${formatDuration(row.total_work_seconds || 0)}</td>
                </tr>
            `).join('') : '<tr><td colspan="5" class="py-8 text-center text-sm text-ink-400">No report rows found.</td></tr>';
        } catch (error) {
            const message = mapApiError(error);
            console.warn('reports api:', message);
            summaryRoot.innerHTML = alertHtml(message);
            rowsRoot.innerHTML = tableMessageRow(5, message, 'danger');
        }
    }

    document.querySelector('[data-report-refresh]')?.addEventListener('click', loadReports);
    document.querySelector('[data-report-export]')?.addEventListener('click', () => {
        const suffix = `${startInput.value || 'start'}-to-${endInput.value || 'end'}`;
        downloadCsv(`report-${suffix}.csv`, [
            'Employee',
            'Present',
            'Late',
            'Leave',
            'Hours',
        ], state.rows.map((row) => [
            row.name || '',
            row.first_check_in ? '1' : '0',
            row.status === 'late' ? '1' : '0',
            row.status === 'leave' ? '1' : '0',
            formatDuration(row.total_work_seconds || 0),
        ]));
    });

    loadReports();
}
