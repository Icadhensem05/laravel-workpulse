import { createTeam, fetchMyTeam, fetchTeamMemberOptions, linkTeamMember } from '../api/team';
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

export function initTeamPage() {
    const membersRoot = document.querySelector('[data-team-members]');
    if (!membersRoot) return;
    let activeTeamId = null;

    async function loadTeam() {
        try {
            const payload = await fetchMyTeam();
            activeTeamId = payload?.team_id || null;
            const members = payload?.members || [];
            membersRoot.innerHTML = members.length ? members.map((member) => `
                <article class="rounded-3xl border border-white/8 bg-white/[0.03] p-5">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex items-start gap-4">
                            <div class="flex h-14 w-14 items-center justify-center rounded-2xl border border-white/8 bg-white/8 text-lg font-semibold text-white">
                                ${escapeHtml(String(member.name || '?').slice(0, 1).toUpperCase())}
                            </div>
                            <div>
                                <p class="text-base font-semibold text-white">${escapeHtml(member.name)}</p>
                                <p class="mt-1 text-sm text-ink-300">${escapeHtml(member.role)} - ${escapeHtml(member.email)}</p>
                                <p class="mt-3 text-sm text-ink-400">${escapeHtml(member.availability || 'offline')}</p>
                            </div>
                        </div>
                        <span class="wp-badge wp-badge-${member.availability === 'online' ? 'success' : 'neutral'}">${escapeHtml(member.availability || 'offline')}</span>
                    </div>
                </article>
            `).join('') : emptyStateHtml('No team members', 'Create or link a member to populate this view.');
        } catch (error) {
            const message = mapApiError(error);
            console.warn('team api:', message);
            membersRoot.innerHTML = alertHtml(message);
        }
    }

    document.querySelector('[data-team-create]')?.addEventListener('click', async () => {
        try {
            await createTeam({ name: document.querySelector('[data-team-name]')?.value || '' });
            document.querySelector('#teamCreateModal')?.classList.remove('is-open');
            await loadTeam();
            showGlobalFeedback('Team created successfully.', 'success');
        } catch (error) {
            showGlobalFeedback(mapApiError(error), 'danger');
        }
    });

    document.querySelector('[data-team-link-open]')?.addEventListener('click', async () => {
        try {
            const payload = await fetchTeamMemberOptions(activeTeamId || 0);
            const select = document.querySelector('[data-team-link-user]');
            if (select) {
                select.innerHTML = (payload?.options || []).map((user) => `<option value="${user.id}">${escapeHtml(user.name)} (${escapeHtml(user.email)})</option>`).join('');
            }
        } catch (error) {
            showGlobalFeedback(mapApiError(error), 'danger');
        }
    });

    document.querySelector('[data-team-link]')?.addEventListener('click', async () => {
        try {
            await linkTeamMember({
                team_id: activeTeamId || 0,
                user_id: Number(document.querySelector('[data-team-link-user]')?.value || 0),
            });
            document.querySelector('#teamLinkModal')?.classList.remove('is-open');
            await loadTeam();
            showGlobalFeedback('Team member linked successfully.', 'success');
        } catch (error) {
            showGlobalFeedback(mapApiError(error), 'danger');
        }
    });

    membersRoot.addEventListener('click', (event) => {
        const button = event.target.closest('[data-team-expand]');
        if (!button) {
            return;
        }

        const card = button.closest('[data-team-member-card]');
        const extra = card?.querySelector('[data-team-member-extra]');
        if (!extra) {
            return;
        }

        extra.classList.toggle('hidden');
        button.textContent = extra.classList.contains('hidden') ? 'View Details' : 'Hide Details';
    });

    loadTeam();
}
