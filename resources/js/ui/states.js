export function emptyStateHtml(title, copy = '') {
    return `
        <div class="wp-empty-state">
            <p class="wp-section-title">${title}</p>
            <p class="wp-helper">${copy}</p>
        </div>
    `;
}

export function alertHtml(message, type = 'danger') {
    return `<div class="wp-alert wp-alert-${type}">${message}</div>`;
}

export function tableMessageRow(colspan, message, variant = 'muted') {
    const colorClass = variant === 'danger' ? 'text-danger-400' : 'text-ink-400';
    return `<tr><td colspan="${colspan}" class="py-8 text-center text-sm ${colorClass}">${message}</td></tr>`;
}
