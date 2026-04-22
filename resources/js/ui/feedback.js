function getFeedbackRoot() {
    return document.querySelector('[data-global-feedback]');
}

function showGlobalFeedback(message, type = 'info', options = {}) {
    const root = getFeedbackRoot();
    if (!root) {
        return;
    }

    const { persist = false, clearAfter = 5000 } = options;

    root.className = 'wp-alert';
    root.classList.remove('hidden', 'wp-alert-info', 'wp-alert-success', 'wp-alert-warning', 'wp-alert-danger');
    root.classList.add(`wp-alert-${type}`);
    root.textContent = message;

    if (root._clearTimer) {
        window.clearTimeout(root._clearTimer);
    }

    if (!persist && clearAfter > 0) {
        root._clearTimer = window.setTimeout(() => {
            clearGlobalFeedback();
        }, clearAfter);
    }
}

function clearGlobalFeedback() {
    const root = getFeedbackRoot();
    if (!root) {
        return;
    }

    if (root._clearTimer) {
        window.clearTimeout(root._clearTimer);
    }

    root.textContent = '';
    root.className = 'hidden rounded-3xl border px-4 py-4 text-sm';
}

export { clearGlobalFeedback, showGlobalFeedback };
