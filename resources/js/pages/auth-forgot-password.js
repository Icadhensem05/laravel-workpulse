import { requestPasswordReset } from '../api/auth';
import { mapApiError } from '../api/client';
import { showGlobalFeedback } from '../ui/feedback';
import { clearFieldError, setFieldError } from '../ui/forms';

export function initAuthForgotPasswordPage() {
    const submitButton = document.querySelector('[data-auth-forgot-submit]');
    const emailField = document.querySelector('#authForgotEmail');
    if (!submitButton) {
        return;
    }

    emailField?.addEventListener('input', () => clearFieldError(emailField));

    submitButton.addEventListener('click', async () => {
        const email = emailField?.value?.trim() || '';

        if (!email) {
            setFieldError(emailField, 'Email is required.');
            showGlobalFeedback('Email is required.', 'warning');
            return;
        }

        submitButton.disabled = true;

        try {
            await requestPasswordReset({ email });
            showGlobalFeedback('If the account exists, a reset link has been sent.', 'success', {
                persist: true,
                clearAfter: 0,
            });
        } catch (error) {
            showGlobalFeedback(mapApiError(error), 'danger', { persist: true, clearAfter: 0 });
        } finally {
            submitButton.disabled = false;
        }
    });
}
