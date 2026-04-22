import { createTask, fetchTasksBoard, moveTask } from '../api/tasks';
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

function priorityVariant(priority) {
    if (priority === 'high') return 'danger';
    if (priority === 'medium') return 'warning';
    return 'neutral';
}

export function initTasksPage() {
    const board = document.querySelector('[data-tasks-board]');
    if (!board) return;
    let draggedCard = null;

    async function loadBoard() {
        try {
            const payload = await fetchTasksBoard();
            const data = payload?.board || {};

            board.querySelectorAll('[data-task-column]').forEach((column) => {
                const key = column.getAttribute('data-task-column');
                const cardsRoot = column.querySelector('[data-task-cards]');
                const countRoot = column.querySelector('[data-task-count]');
                const cards = Array.isArray(data[key]) ? data[key] : [];
                if (countRoot) countRoot.textContent = String(cards.length);
                if (cardsRoot) {
                    cardsRoot.innerHTML = cards.length ? cards.map((card) => `
                        <article class="wp-task-card rounded-3xl border border-white/8 bg-white/[0.03] p-4" draggable="true" data-task-card>
                            <p class="text-base font-semibold text-white">${escapeHtml(card.title)}</p>
                            <div class="mt-4 flex items-center justify-between gap-3">
                                <span class="wp-badge wp-badge-${priorityVariant(card.priority)}">${escapeHtml(card.priority)}</span>
                                <span class="text-xs uppercase tracking-[0.14em] text-ink-400">${escapeHtml(card.due_date || '-')}</span>
                            </div>
                            <div class="mt-4 flex flex-wrap gap-2">
                                <button type="button" class="wp-btn-secondary" data-task-move="${card.id}" data-task-status="todo">To Do</button>
                                <button type="button" class="wp-btn-secondary" data-task-move="${card.id}" data-task-status="in_progress">In Progress</button>
                                <button type="button" class="wp-btn-secondary" data-task-move="${card.id}" data-task-status="review">Review</button>
                                <button type="button" class="wp-btn-secondary" data-task-move="${card.id}" data-task-status="done">Done</button>
                            </div>
                        </article>
                    `).join('') : emptyStateHtml('No tasks', 'Nothing in this column yet.');
                }
            });
        } catch (error) {
            const message = mapApiError(error);
            console.warn('tasks api:', message);
            board.querySelectorAll('[data-task-cards]').forEach((cardsRoot) => {
                cardsRoot.innerHTML = alertHtml(message);
            });
        }
    }

    document.querySelector('[data-task-create]')?.addEventListener('click', async () => {
        try {
            await createTask({
                title: document.querySelector('[data-task-title]')?.value || '',
                description: document.querySelector('[data-task-description]')?.value || '',
                priority: document.querySelector('[data-task-priority]')?.value || 'medium',
                due_date: document.querySelector('[data-task-due]')?.value || '',
            });
            document.querySelector('#taskModal')?.classList.remove('is-open');
            await loadBoard();
            showGlobalFeedback('Task created successfully.', 'success');
        } catch (error) {
            showGlobalFeedback(mapApiError(error), 'danger');
        }
    });

    board.addEventListener('click', async (event) => {
        const button = event.target.closest('[data-task-move]');
        if (!button) return;
        try {
            await moveTask({
                id: Number(button.getAttribute('data-task-move')),
                status: button.getAttribute('data-task-status'),
            });
            await loadBoard();
            showGlobalFeedback('Task status updated successfully.', 'success');
        } catch (error) {
            showGlobalFeedback(mapApiError(error), 'danger');
        }
    });

    board.addEventListener('dragstart', (event) => {
        const card = event.target.closest('[data-task-card]');
        if (!card) {
            return;
        }

        draggedCard = card;
        card.classList.add('is-dragging');
    });

    board.addEventListener('dragend', (event) => {
        const card = event.target.closest('[data-task-card]');
        if (card) {
            card.classList.remove('is-dragging');
        }

        draggedCard = null;
        board.querySelectorAll('[data-task-column]').forEach((column) => {
            column.classList.remove('is-drop-target');
        });
    });

    board.querySelectorAll('[data-task-column]').forEach((column) => {
        column.addEventListener('dragover', (event) => {
            if (!draggedCard) {
                return;
            }

            event.preventDefault();
            column.classList.add('is-drop-target');
        });

        column.addEventListener('dragleave', () => {
            column.classList.remove('is-drop-target');
        });

        column.addEventListener('drop', () => {
            column.classList.remove('is-drop-target');
        });
    });

    loadBoard();
}
