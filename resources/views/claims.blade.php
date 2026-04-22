@php
    $tabs = [
        ['key' => 'header', 'label' => 'Header'],
        ['key' => 'items', 'label' => 'Items'],
        ['key' => 'attachments', 'label' => 'Attachments'],
        ['key' => 'summary', 'label' => 'Summary'],
        ['key' => 'form', 'label' => 'Claim Form'],
        ['key' => 'activity', 'label' => 'Activity'],
    ];
@endphp

<x-layouts.app title="Claims - WorkPulse Laravel" page="claims">
    <section class="wp-panel p-6 sm:p-8">
        <div class="flex flex-col gap-6 xl:flex-row xl:items-center xl:justify-between">
            <div class="max-w-3xl">
                <p class="wp-label">Claims</p>
                <h1 class="wp-page-title mt-3">Claim Management</h1>
                <p class="wp-section-copy mt-4 max-w-2xl">Create, review, and track staff claims from draft to payment with a component-based Laravel UI.</p>
            </div>
            <x-ui.button data-claims-new data-modal-open="claimModal">New Claim</x-ui.button>
        </div>
    </section>

    <section class="grid gap-5 md:grid-cols-2">
        <section class="wp-panel p-6">
            <p class="wp-label">Total Claims</p>
            <p class="mt-4 text-3xl font-semibold tracking-tight text-white" data-claims-total>2</p>
            <p class="wp-helper mt-3">Visible in current scope</p>
        </section>
        <section class="wp-panel p-6">
            <p class="wp-label">Grand Total</p>
            <p class="mt-4 text-3xl font-semibold tracking-tight text-white" data-claims-grand-total>RM 1,107.00</p>
            <p class="wp-helper mt-3">From the filtered result set</p>
        </section>
    </section>

    <x-ui.filter-toolbar>
        <div class="flex flex-1 flex-col gap-3 lg:flex-row">
            <x-ui.input data-claims-month type="month" value="2026-03" class="lg:max-w-52" />
            <x-ui.search-bar data-claims-search class="flex-1" placeholder="Search claim no or employee" />
        </div>
        <div class="flex items-center gap-3">
            <x-ui.button variant="secondary" data-claims-refresh>Refresh</x-ui.button>
        </div>
    </x-ui.filter-toolbar>

    <x-ui.table-shell title="Claims Listing" copy="Filter by month and employee or claim number.">
        <thead>
            <tr>
                <th>Claim No</th>
                <th>Employee</th>
                <th>Month</th>
                <th>Total</th>
                <th>Updated</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody data-claims-rows>
            @foreach ($claims as $claim)
                <tr>
                    <td>
                        <div class="font-semibold text-white">{{ $claim['no'] }}</div>
                        <div class="mt-1 text-xs text-ink-400">{{ $claim['meta'] }}</div>
                    </td>
                    <td>{{ $claim['employee'] }}</td>
                    <td>{{ $claim['month'] }}</td>
                    <td class="wp-table-col-num font-semibold text-white">{{ $claim['total'] }}</td>
                    <td>{{ $claim['updated'] }}</td>
                    <td class="wp-table-col-action"><x-ui.button variant="secondary" data-claim-open>Open</x-ui.button></td>
                </tr>
            @endforeach
        </tbody>
    </x-ui.table-shell>

    <x-ui.modal-shell id="claimModal" title="New Claim" copy="Save a draft, upload support documents, then submit when ready.">
        <div class="space-y-6" data-claim-workspace>
            <input type="hidden" data-claim-id>

            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <h3 class="wp-section-title" data-claim-modal-title>New Claim</h3>
                    <p class="wp-helper mt-2" data-claim-modal-copy>Create or review a claim, then move it through the workflow.</p>
                </div>
                <div class="flex flex-wrap items-center gap-2" data-claim-action-bar>
                    <x-ui.button variant="secondary" data-claim-save>Save Draft</x-ui.button>
                    <x-ui.button data-claim-submit>Submit</x-ui.button>
                    <x-ui.button variant="secondary" data-claim-approve-manager>Manager Approve</x-ui.button>
                    <x-ui.button variant="ghost" data-claim-return-manager>Return</x-ui.button>
                    <x-ui.button variant="danger" data-claim-reject-manager>Reject</x-ui.button>
                    <x-ui.button variant="secondary" data-claim-approve-finance>Finance Approve</x-ui.button>
                    <x-ui.button variant="secondary" data-claim-mark-paid>Mark Paid</x-ui.button>
                    <x-ui.button variant="ghost" data-claim-print>Print Form</x-ui.button>
                </div>
            </div>

            <div class="hidden rounded-3xl border px-4 py-3 text-sm" data-claim-feedback></div>

            <x-ui.tabs :tabs="$tabs" active="header" />

            <div class="grid gap-4 md:grid-cols-2">
                <div class="md:col-span-2 space-y-4 hidden" data-tab-panel="header">
                    <section class="wp-form-section">
                        <div>
                            <h4 class="wp-form-section-title">Employee Header</h4>
                            <p class="wp-form-section-copy">Core employee and claim identification fields.</p>
                        </div>
                        <div class="mt-5 grid gap-4 md:grid-cols-4">
                            <x-ui.input data-claim-header-company label="Company" value="Weststar Engineering" disabled />
                            <x-ui.input data-claim-header-number label="Claim No" value="Will be generated on save" disabled />
                            <x-ui.input data-claim-header-name label="Employee Name" value="Muhammad Irsyad" disabled />
                            <x-ui.input data-claim-header-id label="Employee ID" value="WES-0146" />
                            <x-ui.input data-claim-header-position label="Position" value="Internship" />
                            <x-ui.input data-claim-header-department label="Department" value="ICT" />
                            <x-ui.input data-claim-header-cost-center label="Cost Center" value="KLHQ" />
                            <x-ui.input data-claim-header-month label="Claim Month" type="month" value="2026-03" />
                            <x-ui.input data-claim-header-date label="Claim Date" type="date" value="2026-03-26" />
                            <x-ui.input data-claim-header-advance label="Advance" type="number" value="0" step="0.01" min="0" />
                        </div>
                    </section>

                    <section class="wp-form-section">
                        <div>
                            <h4 class="wp-form-section-title">Remarks</h4>
                            <p class="wp-form-section-copy">Internal explanation or supporting context for reviewers.</p>
                        </div>
                        <div class="mt-5 grid gap-4 md:grid-cols-2">
                            <div class="md:col-span-2">
                                <x-ui.textarea data-claim-header-remarks label="Employee Remarks" rows="4">Claim prepared for monthly submission.</x-ui.textarea>
                            </div>
                        </div>
                    </section>
                </div>

                <div class="md:col-span-2 hidden space-y-4" data-tab-panel="items">
                    <div class="flex flex-col gap-3 rounded-[28px] border border-white/8 bg-white/[0.03] p-5 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <p class="wp-label">Claim Items</p>
                            <p class="wp-helper mt-2">Add one or more lines. Totals recalculate automatically based on category rules.</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="rounded-full border border-white/10 bg-white/[0.04] px-4 py-2 text-xs font-medium text-ink-300" data-claim-mileage-rate>Default mileage rate RM 0.00</span>
                            <x-ui.button variant="secondary" data-claim-add-item>Add Item</x-ui.button>
                        </div>
                    </div>

                    <div class="space-y-4" data-claim-items></div>
                </div>

                <div class="md:col-span-2 hidden space-y-4" data-tab-panel="attachments">
                    <div class="wp-file-upload">
                        <p class="wp-label">Upload Files</p>
                        <p class="wp-section-copy mt-3">Scan a receipt to autofill the claim form, then save and upload supporting files when ready.</p>
                        <div class="mt-4 flex flex-col gap-3 lg:flex-row lg:items-center">
                            <input class="wp-input flex-1" type="file" multiple data-claim-attachment-input>
                            <x-ui.button variant="ghost" data-claim-receipt-scan>Scan Receipt</x-ui.button>
                            <x-ui.button variant="secondary" data-claim-attachment-upload>Upload</x-ui.button>
                        </div>
                        <p class="wp-helper mt-3 hidden" data-claim-upload-progress></p>
                    </div>

                    <div class="space-y-3" data-claim-attachments>
                        <p class="wp-helper">No attachments uploaded yet.</p>
                    </div>
                </div>

                <div class="md:col-span-2 hidden" data-tab-panel="summary">
                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <section class="wp-panel p-6">
                            <p class="wp-label">Travelling</p>
                            <p class="mt-4 text-3xl font-semibold tracking-tight text-white" data-claim-summary-travel>RM 0.00</p>
                        </section>
                        <section class="wp-panel p-6">
                            <p class="wp-label">Transportation</p>
                            <p class="mt-4 text-3xl font-semibold tracking-tight text-white" data-claim-summary-transportation>RM 0.00</p>
                        </section>
                        <section class="wp-panel p-6">
                            <p class="wp-label">Accommodation</p>
                            <p class="mt-4 text-3xl font-semibold tracking-tight text-white" data-claim-summary-accommodation>RM 0.00</p>
                        </section>
                        <section class="wp-panel p-6">
                            <p class="wp-label">Travelling Allowance</p>
                            <p class="mt-4 text-3xl font-semibold tracking-tight text-white" data-claim-summary-travelling-allowance>RM 0.00</p>
                        </section>
                        <section class="wp-panel p-6">
                            <p class="wp-label">Entertainment</p>
                            <p class="mt-4 text-3xl font-semibold tracking-tight text-white" data-claim-summary-entertainment>RM 0.00</p>
                        </section>
                        <section class="wp-panel p-6">
                            <p class="wp-label">Miscellaneous</p>
                            <p class="mt-4 text-3xl font-semibold tracking-tight text-white" data-claim-summary-miscellaneous>RM 0.00</p>
                        </section>
                        <section class="wp-panel p-6">
                            <p class="wp-label">Advance</p>
                            <p class="mt-4 text-3xl font-semibold tracking-tight text-white" data-claim-summary-advance>RM 0.00</p>
                        </section>
                        <section class="wp-panel p-6">
                            <p class="wp-label">Balance Claim</p>
                            <p class="mt-4 text-3xl font-semibold tracking-tight text-white" data-claim-summary-balance>RM 0.00</p>
                            <p class="wp-helper mt-3">Grand total <span data-claim-summary-grand>RM 0.00</span></p>
                        </section>
                    </div>
                </div>

                <div class="md:col-span-2 hidden" data-tab-panel="form">
                    <section class="print-claim-sheet rounded-[28px] border border-white/8 bg-white px-6 py-6 text-slate-900">
                        <div class="mx-auto max-w-xl text-center">
                            <div class="flex items-center justify-center gap-3 bg-slate-900 px-5 py-3 text-white">
                                <span class="text-2xl">★</span>
                                <span class="text-4xl font-black tracking-wide">WESTSTAR</span>
                            </div>
                            <p class="mt-3 text-3xl font-black">ENGINEERING</p>
                        </div>

                        <div class="mt-8 text-center">
                            <h3 class="text-xl font-bold">BORANG TUNTUTAN PERBELANJAAN</h3>
                            <p class="text-sm font-semibold">(CLAIM FORM)</p>
                        </div>

                        <table class="mt-6 w-full border-collapse text-sm">
                            <tbody>
                                <tr><td class="border border-slate-900 px-3 py-2 font-bold">COMPANY:</td><td class="border border-slate-900 px-3 py-2" data-claim-print-company>Weststar Engineering</td></tr>
                                <tr><td class="border border-slate-900 px-3 py-2 font-bold">NAME:</td><td class="border border-slate-900 px-3 py-2" data-claim-print-name>Muhammad Irsyad</td></tr>
                                <tr><td class="border border-slate-900 px-3 py-2 font-bold">POSITION:</td><td class="border border-slate-900 px-3 py-2" data-claim-print-position>Internship</td></tr>
                                <tr><td class="border border-slate-900 px-3 py-2 font-bold">DATE:</td><td class="border border-slate-900 px-3 py-2" data-claim-print-date>2026-03-26</td></tr>
                            </tbody>
                        </table>

                        <table class="mt-6 w-full border-collapse text-xs">
                            <thead>
                                <tr>
                                    <th class="border border-slate-900 px-2 py-2">NO</th>
                                    <th class="border border-slate-900 px-2 py-2">CLAIM TYPE</th>
                                    <th class="border border-slate-900 px-2 py-2">DESCRIPTION</th>
                                    <th class="border border-slate-900 px-2 py-2">AMOUNT</th>
                                    <th class="border border-slate-900 px-2 py-2">TOTAL (RM)</th>
                                </tr>
                            </thead>
                            <tbody data-claim-print-rows>
                                <tr><td class="border border-slate-900 px-2 py-4 text-center">1</td><td class="border border-slate-900 px-2 py-4">TRAVELLING</td><td class="border border-slate-900 px-2 py-4">-</td><td class="border border-slate-900 px-2 py-4 text-right">RM 0.00</td><td class="border border-slate-900 px-2 py-4 text-right">RM 0.00</td></tr>
                            </tbody>
                        </table>

                        <table class="mt-0 w-full border-collapse text-xs">
                            <tbody>
                                <tr><td class="border border-slate-900 px-2 py-2 text-right font-bold">TOTAL CLAIM AMOUNT (RM)</td><td class="border border-slate-900 px-2 py-2 text-right" data-claim-print-grand>RM 0.00</td></tr>
                                <tr><td class="border border-slate-900 px-2 py-2 text-right font-bold">ADVANCE</td><td class="border border-slate-900 px-2 py-2 text-right" data-claim-print-advance>RM 0.00</td></tr>
                                <tr><td class="border border-slate-900 px-2 py-2 text-right font-bold">BALANCE CLAIM AMOUNT</td><td class="border border-slate-900 px-2 py-2 text-right" data-claim-print-balance>RM 0.00</td></tr>
                            </tbody>
                        </table>

                        <div class="mt-10 grid grid-cols-3 gap-5 text-center text-xs">
                            <div>
                                <p class="font-bold">CLAIMED BY</p>
                                <div class="mt-8 border-t border-slate-900"></div>
                                <p class="mt-10" data-claim-print-claimed-by>Muhammad Irsyad</p>
                            </div>
                            <div>
                                <p class="font-bold">APPROVED BY</p>
                                <div class="mt-8 border-t border-slate-900"></div>
                                <p class="mt-10">KETUA JABATAN</p>
                                <p>(Head of Div/Head of Dept)</p>
                            </div>
                            <div>
                                <p class="font-bold">APPROVED BY</p>
                                <div class="mt-8 border-t border-slate-900"></div>
                                <p class="mt-10">CEO/DIRECTOR</p>
                                <p>WESB</p>
                            </div>
                        </div>
                    </section>
                </div>

                <div class="md:col-span-2 hidden space-y-3" data-tab-panel="activity">
                    <div class="space-y-3" data-claim-logs>
                        <p class="wp-helper">No activity yet.</p>
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap gap-3">
                <x-ui.button variant="secondary" data-modal-close>Close</x-ui.button>
                <x-ui.button variant="secondary" data-claim-save>Save Draft</x-ui.button>
                <x-ui.button data-claim-submit>Submit</x-ui.button>
                <x-ui.button variant="ghost" data-claim-print>Print Form</x-ui.button>
            </div>
        </div>
    </x-ui.modal-shell>
</x-layouts.app>
