import { getLocalNormalized, postLocalNormalized } from './client';

export async function fetchTasksBoard() {
    const result = await getLocalNormalized('/tasks/board');
    const data = result.data && typeof result.data === 'object' ? result.data : {};
    const board = {
        todo: [],
        in_progress: [],
        review: [],
        done: [],
    };

    (Array.isArray(data.columns) ? data.columns : []).forEach((column) => {
        if (!column?.id) {
            return;
        }

        board[column.id] = Array.isArray(column.tasks) ? column.tasks : [];
    });

    return {
        board,
        columns: data.columns || [],
    };
}

export async function createTask(payload) {
    const result = await postLocalNormalized('/tasks', payload);
    return {
        ...(result.data && typeof result.data === 'object' ? result.data : {}),
        success: result.success,
        message: result.message,
        errors: result.errors,
        meta: result.meta,
    };
}

export async function moveTask(payload) {
    const status = payload?.status === 'review' ? 'in_progress' : payload?.status;
    const result = await postLocalNormalized('/tasks/move', {
        task_id: payload?.id,
        status,
    });
    return {
        ...(result.data && typeof result.data === 'object' ? result.data : {}),
        success: result.success,
        message: result.message,
        errors: result.errors,
        meta: result.meta,
    };
}
