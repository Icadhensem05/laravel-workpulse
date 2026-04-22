import { fetchDashboardOverview } from '../api/dashboard';
import { mapApiError } from '../api/client';
import { showGlobalFeedback } from '../ui/feedback';

function formatTime(value) {
    if (!value) {
        return '-';
    }

    const date = new Date(value.replace(' ', 'T') + '+08:00');
    return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

function formatHm(totalSeconds) {
    const seconds = Number(totalSeconds || 0);
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    return `${String(hours).padStart(2, '0')}h ${String(minutes).padStart(2, '0')}m`;
}

export function initDashboardPage() {
    const root = document.querySelector('[data-dashboard-date]');

    if (!root) {
        return;
    }

    const selectedDate = root.dataset.dashboardDate;

    fetchDashboardOverview(selectedDate)
        .then((payload) => {
            const data = payload?.today || {};

            document.querySelector('[data-dashboard-month]')?.replaceChildren(document.createTextNode(
                new Date(selectedDate + 'T00:00:00+08:00').toLocaleDateString([], {
                    month: 'long',
                    year: 'numeric',
                }).toUpperCase()
            ));

            document.querySelector('[data-summary-checkin]')?.replaceChildren(document.createTextNode(formatTime(data.check_in)));
            document.querySelector('[data-summary-checkout]')?.replaceChildren(document.createTextNode(formatTime(data.check_out)));
            document.querySelector('[data-summary-break]')?.replaceChildren(document.createTextNode(
                data.break_seconds ? `${Math.round(Number(data.break_seconds) / 60)}m` : '0m'
            ));
            document.querySelector('[data-summary-days]')?.replaceChildren(document.createTextNode(String(payload?.working_days_mtd ?? '0')));
            document.querySelector('[data-stat-actual]')?.replaceChildren(document.createTextNode(formatHm(data.work_seconds)));
            document.querySelector('[data-team-rate]')?.style.setProperty('width', `${payload?.team_rate?.percent ?? 0}%`);
            document.querySelector('[data-team-rate-text]')?.replaceChildren(document.createTextNode(`${payload?.team_rate?.percent ?? 0}%`));

            const timeline = document.querySelector('[data-dashboard-timeline]');
            if (timeline && Array.isArray(payload?.timeline)) {
                timeline.innerHTML = payload.timeline.map((event) => {
                    const label = event.kind === 'break' ? 'Break' : String(event.kind || '').replace('_', ' ');
                    const time = event.start || event.at || '';
                    return `
                        <article class="flex items-center justify-between gap-4 rounded-3xl border border-white/8 bg-white/[0.03] px-5 py-4">
                            <div>
                                <p class="text-base font-semibold text-white">${label}</p>
                                <p class="wp-helper mt-1">${event.source || ''}</p>
                            </div>
                            <p class="text-xl font-semibold tracking-tight text-white">${formatTime(time)}</p>
                        </article>
                    `;
                }).join('');
            }

            const recent = document.querySelector('[data-dashboard-recent]');
            if (recent && Array.isArray(payload?.recent)) {
                recent.innerHTML = payload.recent.map((event) => `
                    <article class="flex items-start justify-between gap-4 rounded-3xl border border-white/8 bg-white/[0.03] px-4 py-4">
                        <div>
                            <p class="text-sm font-semibold text-white">${String(event.event_type || '').replace('_', ' ')}</p>
                            <p class="wp-helper mt-1">${formatTime(event.occurred_at)}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-semibold text-white">Logged</p>
                            <p class="wp-helper mt-1">${event.source || ''}</p>
                        </div>
                    </article>
                `).join('');
            }
        })
        .catch((error) => {
            console.warn('dashboard api:', mapApiError(error));
            showGlobalFeedback(mapApiError(error), 'danger');
        });
}
