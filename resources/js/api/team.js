import { getLocalNormalized, postLocalNormalized } from './client';

export async function fetchMyTeam() {
    const result = await getLocalNormalized('/team/my');
    const data = result.data && typeof result.data === 'object' ? result.data : {};
    const rows = Array.isArray(data.rows) ? data.rows : [];
    const team = rows[0] || null;

    return {
        team_id: team?.id || null,
        members: Array.isArray(team?.members) ? team.members.map((member) => ({
            ...member,
            role: team?.lead_name === member.name ? 'Team Lead' : 'Member',
            availability: 'offline',
        })) : [],
        rows,
    };
}

export async function createTeam(payload) {
    const result = await postLocalNormalized('/team', payload);
    return {
        ...(result.data && typeof result.data === 'object' ? result.data : {}),
        success: result.success,
        message: result.message,
        errors: result.errors,
        meta: result.meta,
    };
}

export async function linkTeamMember(payload) {
    const result = await postLocalNormalized('/team/link', payload);
    return {
        ...(result.data && typeof result.data === 'object' ? result.data : {}),
        success: result.success,
        message: result.message,
        errors: result.errors,
        meta: result.meta,
    };
}

export async function fetchTeamMemberOptions(teamId) {
    const result = await getLocalNormalized('/team/member-options', { params: { team_id: teamId } });
    return result.data;
}
