import { checkPasswordResetToken, completePasswordReset } from '../api/auth';
import { mapApiError } from '../api/client';
import { showGlobalFeedback } from '../ui/feedback';
import { clearFieldError, clearFormErrors, setFieldError } from '../ui/forms';

export function initAuthResetPasswordPage() {
    const submitButton = document.querySelector('[data-auth-reset-submit]');
    const statusRoot = document.querySelector('[data-auth-reset-status]');
    const token = document.querySelector('#authResetToken')?.value || '';
    const passwordField = document.querySelector('#authResetPassword');
    const confirmField = document.querySelector('#authResetPasswordConfirm');

    if (!submitButton || !statusRoot) {
        return;
    }

    [passwordField, confirmField].forEach((field) => {
        field?.addEventListener('input', () => clearFieldError(field));
    });

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

    const verifyToken = async () => {
        if (!token) {
            setStatus('Reset token is missing.', 'danger');
            submitButton.disabled = true;
            return;
        }

        try {
            await checkPasswordResetToken(token);
            setStatus('Reset token verified. You can now set a new password.', 'success');
        } catch (error) {
            setStatus(mapApiError(error), 'danger');
            submitButton.disabled = true;
        }
    };

    submitButton.addEventListener('click', async () => {
        const password = passwordField?.value || '';
        const confirm = confirmField?.value || '';
        clearFormErrors([passwordField, confirmField]);

        if (!password || !confirm) {
            if (!password) setFieldError(passwordField, 'New password is required.');
            if (!confirm) setFieldError(confirmField, 'Please confirm the new password.');
            showGlobalFeedback('Both password fields are required.', 'warning');
            return;
        }

        if (password !== confirm) {
            setFieldError(confirmField, 'Passwords do not match.');
            showGlobalFeedback('Passwords do not match.', 'warning');
            return;
        }

        submitButton.disabled = true;

        try {
            await completePasswordReset({ token, password });
            setStatus('Password reset complete. Redirecting to login...', 'success');
            showGlobalFeedback('Password updated successfully.', 'success');
            window.setTimeout(() => {
                window.location.href = '/login';
            }, 900);
        } catch (error) {
            setStatus(mapApiError(error), 'danger');
            showGlobalFeedback(mapApiError(error), 'danger', { persist: true, clearAfter: 0 });
        } finally {
            submitButton.disabled = false;
        }
    });

    verifyToken();
}
