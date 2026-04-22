import { loginWithPassword } from '../api/auth';
import { mapApiError } from '../api/client';
import { showGlobalFeedback } from '../ui/feedback';
import { clearFieldError, clearFormErrors, setFieldError } from '../ui/forms';

export function initAuthLoginPage() {
    const submitButton = document.querySelector('[data-auth-login-submit]');
    const emailField = document.querySelector('#authLoginEmail');
    const passwordField = document.querySelector('#authLoginPassword');
    if (!submitButton) {
        return;
    }

    [emailField, passwordField].forEach((field) => {
        field?.addEventListener('input', () => clearFieldError(field));
    });

    submitButton.addEventListener('click', async () => {
        const email = emailField?.value?.trim() || '';
        const password = passwordField?.value || '';
        clearFormErrors([emailField, passwordField]);

        if (!email) {
            setFieldError(emailField, 'Email is required.');
        }

        if (!password) {
            setFieldError(passwordField, 'Password is required.');
        }

        if (!email || !password) {
            showGlobalFeedback('Email and password are required.', 'warning');
            return;
        }

        submitButton.disabled = true;

        try {
            await loginWithPassword({ email, password });
            showGlobalFeedback('Login successful. Redirecting...', 'success');
            window.setTimeout(() => {
                window.location.href = '/dashboard';
            }, 500);
        } catch (error) {
            showGlobalFeedback(mapApiError(error), 'danger', { persist: true, clearAfter: 0 });
        } finally {
            submitButton.disabled = false;
        }
    });
}
