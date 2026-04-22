<x-layouts.auth title="Login - WorkPulse Laravel" page="login">
    <div class="max-w-xl">
        <p class="wp-label">Sign In</p>
        <h2 class="wp-page-title mt-3">Access your WorkPulse workspace.</h2>
        <p class="wp-section-copy mt-4">Use your existing employee email and password. Your Laravel session will be created after Auth2 verifies the account and syncs the local user profile.</p>
    </div>

    <div class="mt-8 space-y-5">
        <x-ui.input label="Email" type="email" id="authLoginEmail" placeholder="name@weststar.com" required />
        <x-ui.input label="Password" type="password" id="authLoginPassword" placeholder="Enter password" required />

        <div class="flex items-center justify-between gap-4">
            <label class="wp-choice">
                <span class="wp-choice-box">✓</span>
                <span>Keep me signed in on this browser</span>
            </label>
            <a href="{{ route('forgot-password') }}" class="text-sm font-semibold text-brand-200 hover:text-white">Forgot password?</a>
        </div>

        <div class="flex flex-col gap-3 sm:flex-row">
            <x-ui.button class="sm:min-w-40" data-auth-login-submit>Sign In</x-ui.button>
            <a href="{{ route('forgot-password') }}" class="wp-btn-secondary sm:min-w-40">Request Reset Link</a>
        </div>
    </div>
</x-layouts.auth>
