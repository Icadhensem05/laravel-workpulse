import { getLocalNormalized } from './client';

export async function fetchDashboardOverview(date) {
    const result = await getLocalNormalized('/dashboard/overview', {
        params: { date },
    });
    return result.data;
}
