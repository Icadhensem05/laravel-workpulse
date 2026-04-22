<x-layouts.app title="Assets - WorkPulse Laravel" page="assets">
    <section class="wp-panel p-6 sm:p-8">
        <div class="flex flex-col gap-6 xl:flex-row xl:items-center xl:justify-between">
            <div class="max-w-3xl">
                <p class="wp-label">Assets</p>
                <h1 class="wp-page-title mt-3">Assets Overview</h1>
                <p class="wp-section-copy mt-4 max-w-2xl">Track vehicles, laptops, and office inventory inside the Laravel frontend shell.</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <x-ui.button variant="secondary" data-assets-export>Export CSV</x-ui.button>
                <x-ui.button data-modal-open="assetModal">New Asset</x-ui.button>
            </div>
        </div>
    </section>

    <x-ui.filter-toolbar>
        <x-ui.search-bar class="flex-1" placeholder="Search inventory or assigned user" />
        <div class="flex flex-wrap gap-3">
            <x-ui.select :options="['all' => 'All assets', 'vehicle' => 'Vehicles', 'device' => 'Devices']" selected="all" class="min-w-44" />
            <x-ui.select :options="['all' => 'Any status', 'active' => 'Active', 'use' => 'In Use', 'available' => 'Available']" selected="all" class="min-w-44" />
        </div>
    </x-ui.filter-toolbar>

    <div class="grid gap-5 lg:grid-cols-3" data-assets-grid>
        @foreach ($assets as $asset)
            <article class="wp-panel p-6" data-asset-card data-asset-name="{{ $asset['name'] }}" data-asset-type="{{ $asset['type'] }}" data-asset-assigned="{{ $asset['assigned'] }}" data-asset-status="{{ $asset['status'] }}">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="wp-label">{{ $asset['type'] }}</p>
                        <p class="mt-4 text-xl font-semibold tracking-tight text-white">{{ $asset['name'] }}</p>
                        <p class="wp-helper mt-3">Assigned to {{ $asset['assigned'] }}</p>
                    </div>
                    <x-ui.badge :variant="$asset['status_variant']">{{ $asset['status'] }}</x-ui.badge>
                </div>
                <div class="mt-4">
                    <x-ui.button variant="ghost" data-asset-detail-open>View Details</x-ui.button>
                </div>
            </article>
        @endforeach
    </div>

    <x-ui.modal-shell id="assetModal" title="Create Asset" copy="Create a basic asset in the legacy asset table.">
        <div class="grid gap-4">
            <x-ui.input data-asset-name label="Asset Name" value="Dell Latitude 5440" />
            <x-ui.input data-asset-plate label="Plate / Serial" value="SN-2026-0001" />
            <x-ui.textarea data-asset-description label="Description">Assigned to internal operations.</x-ui.textarea>
        </div>
        <div class="mt-6 flex flex-wrap gap-3">
            <x-ui.button variant="secondary" data-modal-close>Cancel</x-ui.button>
            <x-ui.button data-asset-create>Create</x-ui.button>
        </div>
    </x-ui.modal-shell>

    <x-ui.modal-shell id="assetDetailModal" title="Asset Detail" copy="Quick detail view for the selected asset card.">
        <div class="space-y-4">
            <section class="wp-form-section">
                <div>
                    <h4 class="wp-form-section-title" data-asset-detail-name>Asset</h4>
                    <p class="wp-form-section-copy" data-asset-detail-type>Type</p>
                </div>
                <div class="mt-5 grid gap-4 md:grid-cols-2">
                    <x-ui.input label="Assigned To" data-asset-detail-assigned readonly value="-" />
                    <x-ui.input label="Status" data-asset-detail-status readonly value="-" />
                </div>
            </section>
        </div>
        <div class="mt-6 flex flex-wrap gap-3">
            <x-ui.button variant="secondary" data-modal-close>Close</x-ui.button>
        </div>
    </x-ui.modal-shell>
</x-layouts.app>
