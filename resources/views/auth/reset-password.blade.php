<x-layouts.auth title="Reset Password - WorkPulse Laravel" page="reset-password">
    <div class="max-w-xl">
        <p class="wp-label">Reset Password</p>
        <h2 class="wp-page-title mt-3">Create a new password.</h2>
        <p class="wp-section-copy mt-4">This screen verifies the reset token first, then completes the password reset through Auth2 while keeping the Laravel access flow aligned with live WorkPulse.</p>
    </div>

    <input type="hidden" id="authResetToken" value="{{ $token }}">

    <div class="mt-8 space-y-5">
        <div class="rounded-3xl border border-white/8 bg-white/[0.03] px-4 py-4 text-sm text-ink-200" data-auth-reset-status>
            Checking reset token...
        </div>

        <x-ui.input label="New Password" type="password" id="authResetPassword" placeholder="Minimum 8 characters" required />
        <x-ui.input label="Confirm Password" type="password" id="authResetPasswordConfirm" placeholder="Repeat new password" required />

        <div class="flex flex-col gap-3 sm:flex-row">
            <x-ui.button class="sm:min-w-44" data-auth-reset-submit>Update Password</x-ui.button>
            <a href="{{ route('login') }}" class="wp-btn-secondary sm:min-w-40">Back To Login</a>
        </div>
    </div>
</x-layouts.auth>
