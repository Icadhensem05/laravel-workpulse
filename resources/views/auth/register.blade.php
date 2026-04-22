<x-layouts.auth title="Register - WorkPulse Laravel" page="register">
    <div class="max-w-xl">
        <p class="wp-label">Registration Disabled</p>
        <h2 class="wp-page-title mt-3">Accounts are managed centrally in Auth2.</h2>
        <p class="wp-section-copy mt-4">Create new employees in Auth2 admin first. When they sign in to Laravel WorkPulse, the local user profile will be created or synced automatically.</p>
    </div>

    <div class="mt-8 space-y-5">
        <div class="rounded-3xl border border-warning-500/25 bg-warning-950/90 px-4 py-4 text-sm text-amber-200">
            Self-service registration is disabled. Use Auth2 admin to create accounts for live users.
        </div>

        <div class="flex flex-col gap-3 sm:flex-row">
            <a href="{{ route('login') }}" class="wp-btn-secondary sm:min-w-40">Back To Login</a>
        </div>
    </div>
</x-layouts.auth>
