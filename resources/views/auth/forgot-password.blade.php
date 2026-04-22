<x-layouts.auth title="Forgot Password - WorkPulse Laravel" page="forgot-password">
    <div class="max-w-xl">
        <p class="wp-label">Password Reset</p>
        <h2 class="wp-page-title mt-3">Request a reset link.</h2>
        <p class="wp-section-copy mt-4">Enter your employee email. If the account exists, Auth2 will send a reset link and Laravel will complete the reset flow through the connected Auth2 APIs.</p>
    </div>

    <div class="mt-8 space-y-5">
        <x-ui.input label="Email" type="email" id="authForgotEmail" placeholder="name@weststar.com" required />

        <div class="flex flex-col gap-3 sm:flex-row">
            <x-ui.button class="sm:min-w-44" data-auth-forgot-submit>Send Reset Link</x-ui.button>
            <a href="{{ route('login') }}" class="wp-btn-secondary sm:min-w-40">Back To Login</a>
        </div>
    </div>
</x-layouts.auth>
