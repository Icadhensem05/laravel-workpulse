import { getLocalNormalized, postLocalNormalized } from './client';

function formatStatusLabel(status) {
    const value = String(status || '').trim().toLowerCase();

    switch (value) {
        case 'on_time':
            return 'On time';
        case 'late':
            return 'Late';
        case 'absent':
            return 'Absent';
        case 'present':
            return 'Present';
        case 'leave':
            return 'Leave';
        case 'weekend':
            return 'Weekend';
        default:
            return value
                .split('_')
                .filter(Boolean)
                .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
                .join(' ') || '-';
    }
}

function formatMinutesToHm(totalMinutes) {
    const minutes = Number(totalMinutes || 0);
    const hoursPart = String(Math.floor(minutes / 60)).padStart(2, '0');
    const minutesPart = String(minutes % 60).padStart(2, '0');
    return `${hoursPart}:${minutesPart}`;
}

function mapEntry(entry) {
    const data = entry && typeof entry === 'object' ? entry : {};

    return {
        ...data,
        work_date: data.attendance_date || null,
        first_check_in: data.check_in_at || null,
        last_check_out: data.check_out_at || null,
        break_hm: formatMinutesToHm(data.break_minutes),
        work_hm: formatMinutesToHm(data.total_minutes),
        status_label: formatStatusLabel(data.status),
        total_work_seconds: Number(data.total_minutes || 0) * 60,
        total_break_seconds: Number(data.break_minutes || 0) * 60,
    };
}

function mapAdminRow(row) {
    const data = row && typeof row === 'object' ? row : {};
    const entry = mapEntry(data.entry || {});

    return {
        user_id: data.user_id,
        name: data.name || '',
        email: data.email || '',
        employee_code: data.employee_code || '',
        department: data.department || '',
        status: entry.status || 'absent',
        ...entry,
    };
}

export async function fetchAttendanceList(params) {
    const result = await getLocalNormalized('/attendance/entries', { params });
    const data = result.data && typeof result.data === 'object' ? result.data : {};

    return {
        ...data,
        rows: Array.isArray(data.rows) ? data.rows.map(mapEntry) : [],
    };
}

export async function fetchAttendanceStatus() {
    const result = await getLocalNormalized('/attendance/status');
    const data = result.data && typeof result.data === 'object' ? result.data : {};
    const entry = data.entry && typeof data.entry === 'object' ? data.entry : null;

    return {
        ...data,
        next_event: data.next_action || 'check_in',
        checked_in: Boolean(entry?.check_in_at) && !entry?.check_out_at,
        entry: entry ? mapEntry(entry) : null,
    };
}

export async function postAttendanceEvent(payload) {
    const result = await postLocalNormalized('/attendance/event', {
        action: payload?.event || payload?.action || 'check_in',
    });
    return {
        ...(result.data && typeof result.data === 'object' ? result.data : {}),
        success: result.success,
        message: result.message,
        errors: result.errors,
        meta: result.meta,
    };
}

export async function saveAttendanceEntry(payload) {
    const result = await postLocalNormalized('/attendance/entries/upsert', {
        date: payload?.date || '',
        check_in_at: payload?.check_in || payload?.check_in_at || null,
        check_out_at: payload?.check_out || payload?.check_out_at || null,
        break_minutes: payload?.break_minutes ?? 0,
        remarks: payload?.remarks || null,
    });
    return {
        ...(result.data && typeof result.data === 'object' ? result.data : {}),
        success: result.success,
        message: result.message,
        errors: result.errors,
        meta: result.meta,
    };
}

export async function fetchAttendanceAdminList(date) {
    const result = await getLocalNormalized('/attendance/admin/daily', { params: { date } });
    const data = result.data && typeof result.data === 'object' ? result.data : {};

    return {
        ...data,
        rows: Array.isArray(data.rows) ? data.rows.map(mapAdminRow) : [],
    };
}

export async function saveAttendanceAdminEntry(payload) {
    const result = await postLocalNormalized('/attendance/admin/daily', {
        user_id: payload?.user_id,
        date: payload?.date || '',
        status: payload?.status || null,
        check_in_at: payload?.check_in || payload?.check_in_at || null,
        check_out_at: payload?.check_out || payload?.check_out_at || null,
        break_minutes: payload?.break_minutes ?? 0,
        remarks: payload?.remarks || null,
    });
    return {
        ...(result.data && typeof result.data === 'object' ? result.data : {}),
        success: result.success,
        message: result.message,
        errors: result.errors,
        meta: result.meta,
    };
}
