<x-layouts.app title="Admin - WorkPulse Laravel" page="admin">
    <section class="wp-panel p-6 sm:p-8">
        <div class="flex flex-col gap-6 xl:flex-row xl:items-center xl:justify-between">
            <div class="max-w-3xl">
                <p class="wp-label">Admin</p>
                <h1 class="wp-page-title mt-3">Admin Workspace</h1>
                <p class="wp-section-copy mt-4 max-w-2xl">Manage core operational controls for users, approvals, assets, and application settings from one Laravel admin surface.</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <x-ui.button variant="secondary" data-admin-refresh>Refresh Workspace</x-ui.button>
            </div>
        </div>
    </section>

    <section class="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
        <x-stat-card label="Users" value="0" meta="Directory records" icon="US" data-admin-users-count />
        <x-stat-card label="Approvals" value="0" meta="Approval mappings" icon="AP" data-admin-approvals-count />
        <x-stat-card label="Assets" value="0" meta="Admin-visible assets" icon="AS" data-admin-assets-count />
        <x-stat-card label="Settings" value="Ready" meta="Current user config" icon="ST" />
    </section>

    <section class="grid gap-5 xl:grid-cols-[1.1fr_0.9fr]">
        <x-ui.table-shell title="User Management" copy="Read-only staff directory loaded from the Laravel users table.">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody data-admin-users-rows>
                <tr><td colspan="4" class="py-8 text-center text-sm text-ink-400">Loading users...</td></tr>
            </tbody>
        </x-ui.table-shell>

        <section class="wp-panel p-6">
            <div>
                <p class="wp-label">Mapping</p>
                <h2 class="wp-section-title mt-2">Manual Person ID Link</h2>
                <p class="wp-section-copy mt-2">Link an employee code to an existing Laravel user record.</p>
            </div>

            <div class="mt-6 grid gap-4">
                <x-ui.input label="Employee Code" data-admin-person-id placeholder="e.g. WES-0146" />
                <x-ui.select label="Target User" data-admin-person-user :options="['' => 'Select a user']" selected="" />
                <div class="flex flex-wrap gap-3">
                    <x-ui.button data-admin-person-link>Link Person</x-ui.button>
                </div>
                <p class="wp-helper">This updates the local Laravel user directory.</p>
            </div>
        </section>
    </section>

    <x-ui.table-shell title="Approval Management" copy="Edit approval-related settings stored in Laravel.">
        <thead>
            <tr>
                <th>Module</th>
                <th>Key</th>
                <th colspan="2">Value</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody data-admin-approvals-rows>
            <tr><td colspan="5" class="py-8 text-center text-sm text-ink-400">Loading approvals...</td></tr>
        </tbody>
    </x-ui.table-shell>

    <section class="grid gap-5 xl:grid-cols-[1fr_1fr]">
        <x-ui.table-shell title="Asset Oversight" copy="Admin-visible asset inventory from the Laravel asset module.">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Plate</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody data-admin-assets-rows>
                <tr><td colspan="3" class="py-8 text-center text-sm text-ink-400">Loading assets...</td></tr>
            </tbody>
        </x-ui.table-shell>

        <section class="wp-panel p-6">
            <div>
                <p class="wp-label">Settings</p>
                <h2 class="wp-section-title mt-2">Application Preferences</h2>
                <p class="wp-section-copy mt-2">Persist notification and schedule settings through Laravel admin APIs.</p>
            </div>

            <div class="mt-6 grid gap-4">
                <x-ui.checkbox label="Check-in reminders" data-admin-setting-checkin />
                <x-ui.checkbox label="Break alerts" data-admin-setting-break />
                <x-ui.checkbox label="Weekly report email" data-admin-setting-weekly />
                <x-ui.checkbox label="Overtime enabled" data-admin-setting-overtime />
                <div class="grid gap-4 md:grid-cols-2">
                    <x-ui.input label="Start Time" type="time" data-admin-setting-start />
                    <x-ui.input label="End Time" type="time" data-admin-setting-end />
                </div>
                <div class="flex flex-wrap gap-3">
                    <x-ui.button data-admin-settings-save>Save Settings</x-ui.button>
                </div>
            </div>
        </section>
    </section>
</x-layouts.app>
