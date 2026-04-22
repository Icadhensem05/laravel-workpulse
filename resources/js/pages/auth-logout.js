import { logoutCurrentSession } from '../api/auth';
import { mapApiError } from '../api/client';

export function initAuthLogoutPage() {
    const statusRoot = document.querySelector('[data-auth-logout-status]');
    if (!statusRoot) {
        return;
    }

    const setStatus = (message, type = 'neutral') => {
        statusRoot.textContent = message;
        statusRoot.className = 'rounded-3xl border px-4 py-4 text-sm';

        if (type === 'success') {
            statusRoot.classList.add('border-success-500/25', 'bg-success-950/90', 'text-green-200');
            return;
        }

        if (type === 'danger') {
            statusRoot.classList.add('border-danger-500/25', 'bg-danger-950/90', 'text-red-200');
            return;
        }

        statusRoot.classList.add('border-white/8', 'bg-white/[0.03]', 'text-ink-200');
    };

    logoutCurrentSession()
        .then(() => {
            setStatus('Session cleared. Redirecting to login...', 'success');
            window.setTimeout(() => {
                window.location.href = '/login';
            }, 600);
        })
        .catch((error) => {
            const message = mapApiError(error);
            const isExpiredSession = /unauthenticated|session has expired|sign in again/i.test(String(message));

            if (isExpiredSession) {
                setStatus('Your session is already signed out. Redirecting to login...', 'success');
                window.setTimeout(() => {
                    window.location.href = '/login';
                }, 600);
                return;
            }

            setStatus(message, 'danger');
        });
}
