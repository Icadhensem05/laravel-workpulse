import { getLocalNormalized } from './client';

export async function fetchReportSummary(params) {
    const month = String(params?.end || params?.start || new Date().toISOString().slice(0, 7)).slice(0, 7);
    const result = await getLocalNormalized('/reports/summary', { params: { month } });
    const data = result.data && typeof result.data === 'object' ? result.data : {};
    const summary = data.summary && typeof data.summary === 'object' ? data.summary : {};
    const attendanceTotal = Number(summary.attendance_total || 0);

    return {
        insights: {
            on_time_rate: attendanceTotal > 0 ? Math.min(1, Number(summary.on_time_total || 0) / attendanceTotal) : 0,
            avg_break_seconds: Number(summary.avg_break_minutes || 0) * 60,
            working_days: Number(summary.working_days || 0),
        },
    };
}

export async function fetchAttendanceReportList(params) {
    const result = await getLocalNormalized('/reports/attendance', { params: { date: params?.date || '' } });
    const data = result.data && typeof result.data === 'object' ? result.data : {};

    return {
        ...data,
        rows: Array.isArray(data.rows) ? data.rows.map((row) => ({
            ...row,
            name: row.user_name,
            first_check_in: row.check_in_at,
            total_work_seconds: Number(row.total_minutes || 0) * 60,
        })) : [],
    };
}
