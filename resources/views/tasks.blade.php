<x-layouts.app title="Tasks - WorkPulse Laravel" page="tasks">
    <section class="wp-panel p-6 sm:p-8">
        <div class="flex flex-col gap-6 xl:flex-row xl:items-center xl:justify-between">
            <div class="max-w-3xl">
                <p class="wp-label">Boards</p>
                <h1 class="wp-page-title mt-3">Task Management</h1>
                <p class="wp-section-copy mt-4 max-w-2xl">Organize work in a Kanban-style layout using the Laravel component layer.</p>
            </div>
            <x-ui.button data-modal-open="taskModal">New Task</x-ui.button>
        </div>
    </section>

    <section class="grid gap-5 xl:grid-cols-4" data-tasks-board>
        @foreach ($columns as $key => $cards)
            <section class="wp-panel p-5" data-task-column="{{ $key }}">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="wp-section-title">{{ ucwords(str_replace('_', ' ', $key)) }}</h2>
                    <x-ui.badge variant="{{ $key === 'done' ? 'success' : ($key === 'review' ? 'warning' : 'neutral') }}" data-task-count>{{ count($cards) }}</x-ui.badge>
                </div>

                <div class="mt-5 space-y-4" data-task-cards>
                    @foreach ($cards as $card)
                        <article class="wp-task-card rounded-3xl border border-white/8 bg-white/[0.03] p-4" draggable="true" data-task-card>
                            <p class="text-base font-semibold text-white">{{ $card['title'] }}</p>
                            <div class="mt-4 flex items-center justify-between gap-3">
                                <x-ui.badge variant="{{ $card['priority'] === 'High' ? 'danger' : ($card['priority'] === 'Done' ? 'success' : 'warning') }}">{{ $card['priority'] }}</x-ui.badge>
                                <span class="text-xs uppercase tracking-[0.14em] text-ink-400">{{ $card['due'] }}</span>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        @endforeach
    </section>

    <x-ui.modal-shell id="taskModal" title="Create Task" copy="Task creation shell for the future board workflow.">
        <div class="grid gap-4 md:grid-cols-2">
            <div class="md:col-span-2"><x-ui.input data-task-title label="Title" value="Prepare monthly attendance pack" /></div>
            <div class="md:col-span-2"><x-ui.textarea data-task-description label="Description">Compile attendance summary, leave overlaps, and export package for HR.</x-ui.textarea></div>
            <x-ui.select data-task-priority label="Priority" :options="['medium' => 'Medium', 'high' => 'High', 'low' => 'Low']" selected="medium" />
            <x-ui.date-field data-task-due label="Due Date" value="2026-03-29" />
        </div>
        <div class="mt-6 flex flex-wrap gap-3">
            <x-ui.button variant="secondary" data-modal-close>Cancel</x-ui.button>
            <x-ui.button data-task-create>Create</x-ui.button>
        </div>
    </x-ui.modal-shell>
</x-layouts.app>
