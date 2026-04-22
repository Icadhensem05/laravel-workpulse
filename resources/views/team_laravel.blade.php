<x-layouts.app title="Team - WorkPulse Laravel" page="team">
    <section class="wp-panel p-6 sm:p-8">
        <div class="flex flex-col gap-6 xl:flex-row xl:items-center xl:justify-between">
            <div class="max-w-3xl">
                <p class="wp-label">People</p>
                <h1 class="wp-page-title mt-3">Your Team</h1>
                <p class="wp-section-copy mt-4 max-w-2xl">Manage members, roles, and recent activity with a cleaner team view.</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <x-ui.button variant="secondary" data-modal-open="teamCreateModal">Create Team</x-ui.button>
                <x-ui.button data-team-link-open data-modal-open="teamLinkModal">Link Member</x-ui.button>
            </div>
        </div>
    </section>

    <section class="wp-panel p-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="wp-section-title">Members</h2>
                <p class="wp-section-copy mt-2">Organization roster with quick availability signals.</p>
            </div>
            <x-ui.select :options="['ops' => 'Operations', 'ict' => 'ICT', 'hr' => 'HR']" selected="ict" class="lg:max-w-56" />
        </div>

        <div class="mt-6 grid gap-4 lg:grid-cols-2" data-team-members>
            @foreach ($members as $member)
                <article class="rounded-3xl border border-white/8 bg-white/[0.03] p-5" data-team-member-card>
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex items-start gap-4">
                            <div class="flex h-14 w-14 items-center justify-center rounded-2xl border border-white/8 bg-white/8 text-lg font-semibold text-white">
                                {{ strtoupper(substr($member['name'], 0, 1)) }}
                            </div>
                            <div>
                                <p class="text-base font-semibold text-white">{{ $member['name'] }}</p>
                                <p class="mt-1 text-sm text-ink-300">{{ $member['role'] }} • {{ $member['email'] }}</p>
                                <p class="mt-3 text-sm text-ink-400">{{ $member['note'] }}</p>
                            </div>
                        </div>
                        <x-ui.badge :variant="$member['status'] === 'Online' ? 'success' : 'neutral'">{{ $member['status'] }}</x-ui.badge>
                    </div>
                    <div class="mt-4 hidden text-sm text-ink-300" data-team-member-extra>
                        <p>Availability detail: {{ $member['note'] }}</p>
                    </div>
                    <div class="mt-4">
                        <x-ui.button variant="ghost" data-team-expand>View Details</x-ui.button>
                    </div>
                </article>
            @endforeach
        </div>
    </section>

    <x-ui.modal-shell id="teamCreateModal" title="Create Team" copy="Create a basic team shell in the legacy backend.">
        <div class="grid gap-4">
            <x-ui.input data-team-name label="Team Name" value="Operations Team" />
        </div>
        <div class="mt-6 flex flex-wrap gap-3">
            <x-ui.button variant="secondary" data-modal-close>Cancel</x-ui.button>
            <x-ui.button data-team-create>Create</x-ui.button>
        </div>
    </x-ui.modal-shell>

    <x-ui.modal-shell id="teamLinkModal" title="Link Member" copy="Add an existing user into your current team.">
        <div class="grid gap-4">
            <x-ui.select data-team-link-user label="User" :options="['' => 'Loading members...']" selected="" />
        </div>
        <div class="mt-6 flex flex-wrap gap-3">
            <x-ui.button variant="secondary" data-modal-close>Cancel</x-ui.button>
            <x-ui.button data-team-link>Link</x-ui.button>
        </div>
    </x-ui.modal-shell>
</x-layouts.app>
