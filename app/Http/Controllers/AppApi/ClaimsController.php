<?php

namespace App\Http\Controllers\AppApi;

use App\Http\Controllers\Controller;
use App\Models\Claim;
use App\Models\ClaimAttachment;
use App\Models\ClaimCategory;
use App\Models\ClaimItem;
use App\Models\ClaimPayment;
use App\Models\ClaimStatusLog;
use App\Services\AnthropicReceiptExtractor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

class ClaimsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $claims = Claim::query()
            ->with(['employee', 'attachments'])
            ->when($user->role !== 'admin', fn ($query) => $query->where('employee_user_id', $user->id))
            ->latest('id')
            ->get();

        return response()->json([
            'success' => true,
            'claims' => $claims->map(fn (Claim $claim) => $this->transformClaim($claim))->values(),
            'summary' => [
                'total' => $claims->count(),
                'grand_total' => (float) $claims->sum('grand_total'),
            ],
            'categories' => ClaimCategory::query()->where('is_active', true)->orderBy('display_order')->get(['id', 'code', 'name']),
        ]);
    }

    public function show(Request $request, Claim $claim): JsonResponse
    {
        $this->authorizeClaim($request, $claim);

        $claim->load(['employee', 'items.category', 'logs.actor', 'attachments.uploader', 'payments.recorder']);

        return response()->json([
            'success' => true,
            'claim' => $this->transformClaim($claim),
            'items' => $claim->items->map(fn (ClaimItem $item) => $this->transformItem($item))->values(),
            'logs' => $claim->logs->map(fn (ClaimStatusLog $log) => [
                'action_name' => $log->action_name,
                'from_status' => $log->from_status,
                'to_status' => $log->to_status,
                'remarks' => $log->remarks,
                'action_role' => $log->action_role,
                'created_at' => optional($log->created_at)->toDateTimeString(),
                'user_name' => $log->actor?->display_name,
            ])->values(),
            'attachments' => $claim->attachments->map(fn (ClaimAttachment $attachment) => $this->transformAttachment($attachment))->values(),
            'payments' => $claim->payments->map(fn (ClaimPayment $payment) => $this->transformPayment($payment))->values(),
            'categories' => ClaimCategory::query()->where('is_active', true)->orderBy('display_order')->get(['id', 'code', 'name']),
            'default_mileage_rate' => 0.0,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        [$payload, $items, $summary] = $this->validateClaimPayload($request);

        $claim = DB::transaction(function () use ($user, $payload, $items, $summary) {
            $claim = Claim::create([
                'claim_no' => $this->generateClaimNumber($payload['claim_month']),
                'employee_user_id' => $user->id,
                'company_name' => 'Weststar Engineering',
                'employee_name' => $user->display_name,
                'employee_code' => $payload['employee_code'] ?? $user->employee_code,
                'position_title' => $payload['position_title'] ?? $user->job_title,
                'department' => $payload['department'] ?? $user->department,
                'cost_center' => $payload['cost_center'] ?? $user->cost_center,
                'claim_month' => $payload['claim_month'],
                'claim_date' => $payload['claim_date'],
                'total_travelling' => $summary['totals']['travelling'],
                'total_transportation' => $summary['totals']['transportation'],
                'total_accommodation' => $summary['totals']['accommodation'],
                'total_travelling_allowance' => $summary['totals']['travelling_allowance'],
                'total_entertainment' => $summary['totals']['entertainment'],
                'total_miscellaneous' => $summary['totals']['miscellaneous'],
                'advance_amount' => $summary['advance_amount'],
                'grand_total' => $summary['grand_total'],
                'balance_claim' => $summary['balance_claim'],
                'employee_remarks' => $payload['employee_remarks'] ?? null,
                'status' => 'draft',
            ]);

            $this->persistItems($claim, $items);
            $this->logStatus($claim, null, 'draft', 'claim_created', $user->id, $user->role, null);

            return $claim->fresh(['employee', 'attachments']);
        });

        return response()->json([
            'success' => true,
            'message' => 'Claim draft saved successfully.',
            'claim' => $this->transformClaim($claim),
            'summary' => $summary,
        ], 201);
    }

    public function update(Request $request, Claim $claim): JsonResponse
    {
        $user = $request->user();
        $this->authorizeClaim($request, $claim, editableOnly: true);
        [$payload, $items, $summary] = $this->validateClaimPayload($request);

        DB::transaction(function () use ($claim, $payload, $items, $summary, $user) {
            $claim->fill([
                'employee_code' => $payload['employee_code'] ?? $claim->employee_code,
                'position_title' => $payload['position_title'] ?? $claim->position_title,
                'department' => $payload['department'] ?? $claim->department,
                'cost_center' => $payload['cost_center'] ?? $claim->cost_center,
                'claim_month' => $payload['claim_month'],
                'claim_date' => $payload['claim_date'],
                'total_travelling' => $summary['totals']['travelling'],
                'total_transportation' => $summary['totals']['transportation'],
                'total_accommodation' => $summary['totals']['accommodation'],
                'total_travelling_allowance' => $summary['totals']['travelling_allowance'],
                'total_entertainment' => $summary['totals']['entertainment'],
                'total_miscellaneous' => $summary['totals']['miscellaneous'],
                'advance_amount' => $summary['advance_amount'],
                'grand_total' => $summary['grand_total'],
                'balance_claim' => $summary['balance_claim'],
                'employee_remarks' => $payload['employee_remarks'] ?? null,
            ])->save();

            $claim->items()->delete();
            $this->persistItems($claim, $items);
            $this->logStatus($claim, $claim->status, $claim->status, 'draft_saved', $user->id, $user->role, null);
        });

        return response()->json([
            'success' => true,
            'message' => 'Claim draft updated successfully.',
            'claim' => $this->transformClaim($claim->fresh(['employee', 'attachments'])),
            'summary' => $summary,
        ]);
    }

    public function submit(Request $request, Claim $claim): JsonResponse
    {
        $user = $request->user();
        $this->authorizeClaim($request, $claim, editableOnly: true);

        if (! in_array($claim->status, ['draft', 'returned_for_amendment'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Only draft or returned claims can be submitted.',
            ], 422);
        }

        $fromStatus = $claim->status;
        $claim->forceFill([
            'status' => 'submitted',
            'submitted_at' => now(),
        ])->save();

        $this->logStatus($claim, $fromStatus, 'submitted', 'claim_submitted', $user->id, $user->role, null);

        return response()->json([
            'success' => true,
            'message' => 'Claim submitted successfully.',
            'claim' => $this->transformClaim($claim->fresh(['employee', 'attachments'])),
        ]);
    }

    public function action(Request $request, Claim $claim): JsonResponse
    {
        $user = $request->user();
        $data = Validator::make($request->all(), [
            'action' => ['required', 'string', 'in:manager_approve,manager_reject,manager_return,finance_approve,mark_paid'],
            'remarks' => ['nullable', 'string'],
            'payment_reference' => ['nullable', 'string', 'max:120'],
            'payment_date' => ['nullable', 'date'],
            'payment_method' => ['nullable', 'string', 'max:120'],
            'payment_amount' => ['nullable', 'numeric', 'min:0'],
        ])->validate();

        $transition = $this->resolveActionTransition($user->role, $claim->status, $data['action']);

        DB::transaction(function () use ($claim, $data, $transition, $user) {
            $fromStatus = $claim->status;

            $claim->forceFill([
                'status' => $transition['to_status'],
                'manager_remarks' => $transition['target_field'] === 'manager_remarks' ? ($data['remarks'] ?? $claim->manager_remarks) : $claim->manager_remarks,
                'finance_remarks' => $transition['target_field'] === 'finance_remarks' ? ($data['remarks'] ?? $claim->finance_remarks) : $claim->finance_remarks,
                'approved_at' => $transition['to_status'] === 'approved' ? now() : $claim->approved_at,
                'paid_at' => $transition['to_status'] === 'paid' ? now() : $claim->paid_at,
            ])->save();

            if ($data['action'] === 'mark_paid') {
                ClaimPayment::create([
                    'claim_id' => $claim->id,
                    'payment_reference' => $data['payment_reference'] ?? null,
                    'payment_date' => $data['payment_date'] ?? now()->toDateString(),
                    'payment_method' => $data['payment_method'] ?? null,
                    'payment_amount' => (float) ($data['payment_amount'] ?? $claim->balance_claim),
                    'remarks' => $data['remarks'] ?? null,
                    'recorded_by_user_id' => $user->id,
                ]);
            }

            $this->logStatus(
                $claim,
                $fromStatus,
                $transition['to_status'],
                $data['action'],
                $user->id,
                $user->role,
                $data['remarks'] ?? null
            );
        });

        return response()->json([
            'success' => true,
            'message' => $transition['message'],
            'claim' => $this->transformClaim($claim->fresh(['employee', 'attachments'])),
        ]);
    }

    public function uploadAttachment(Request $request, Claim $claim): JsonResponse
    {
        $this->authorizeClaim($request, $claim, editableOnly: true);

        $data = Validator::make($request->all(), [
            'file' => ['required', 'file', 'max:10240'],
        ])->validate();

        $file = $data['file'];
        $storedPath = $file->store("claim-attachments/{$claim->id}", 'public');

        $attachment = ClaimAttachment::create([
            'claim_id' => $claim->id,
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $storedPath,
            'mime_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
            'uploaded_by_user_id' => $request->user()->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Attachment uploaded successfully.',
            'attachment' => $this->transformAttachment($attachment->fresh(['uploader'])),
        ], 201);
    }

    public function extractReceipt(Request $request, AnthropicReceiptExtractor $extractor): JsonResponse
    {
        $data = Validator::make($request->all(), [
            'file' => ['required', 'file', 'mimetypes:image/jpeg,image/png,image/webp,image/gif,application/pdf', 'max:10240'],
        ])->validate();

        $categories = ClaimCategory::query()
            ->where('is_active', true)
            ->orderBy('display_order')
            ->get(['code', 'name'])
            ->map(fn (ClaimCategory $category) => [
                'code' => $category->code,
                'name' => $category->name,
            ])
            ->values()
            ->all();

        try {
            $receipt = $extractor->extract($data['file'], $categories);
        } catch (RuntimeException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Receipt extracted successfully.',
            'receipt' => $receipt,
        ]);
    }

    public function deleteAttachment(Request $request, Claim $claim, ClaimAttachment $attachment): JsonResponse
    {
        $this->authorizeClaim($request, $claim, editableOnly: true);

        abort_if($attachment->claim_id !== $claim->id, 404, 'Attachment not found.');

        Storage::disk('public')->delete($attachment->file_path);
        $attachment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Attachment deleted successfully.',
        ]);
    }

    private function validateClaimPayload(Request $request): array
    {
        $payload = Validator::make($request->all(), [
            'employee_code' => ['nullable', 'string', 'max:100'],
            'position_title' => ['nullable', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'cost_center' => ['nullable', 'string', 'max:255'],
            'claim_month' => ['required', 'date_format:Y-m'],
            'claim_date' => ['required', 'date'],
            'advance_amount' => ['nullable', 'numeric', 'min:0'],
            'employee_remarks' => ['nullable', 'string'],
            'items' => ['array'],
        ])->validate();

        $categories = ClaimCategory::query()->where('is_active', true)->get()->keyBy('code');
        $inputItems = collect($request->input('items', []));

        $items = $inputItems->values()->map(function ($item, $index) use ($categories) {
            $category = $categories->get($item['category_code'] ?? '');
            abort_if(! $category, 422, 'Invalid claim category.');

            $distance = max(0, (float) ($item['distance_km'] ?? 0));
            $mileageRate = max(0, (float) ($item['mileage_rate'] ?? 0));
            $toll = max(0, (float) ($item['toll_amount'] ?? 0));
            $parking = max(0, (float) ($item['parking_amount'] ?? 0));
            $rateAmount = max(0, (float) ($item['rate_amount'] ?? 0));
            $quantity = max(0, (float) ($item['quantity_value'] ?? 1));
            $amount = max(0, (float) ($item['amount'] ?? 0));
            $mileageAmount = 0.0;
            $totalAmount = $amount;

            if ($category->code === 'travelling') {
                $mileageAmount = round($distance * $mileageRate, 2);
                $amount = $mileageAmount;
                $totalAmount = round($mileageAmount + $toll + $parking, 2);
            } elseif ($category->code === 'travelling_allowance') {
                $amount = $rateAmount;
                $totalAmount = round($rateAmount * ($quantity > 0 ? $quantity : 1), 2);
            } else {
                $totalAmount = round($amount, 2);
            }

            return [
                'category_id' => $category->id,
                'category_code' => $category->code,
                'line_no' => $index + 1,
                'item_date' => $item['item_date'] ?? now()->toDateString(),
                'from_location' => $item['from_location'] ?? null,
                'to_location' => $item['to_location'] ?? null,
                'purpose' => $item['purpose'] ?? null,
                'receipt_no' => $item['receipt_no'] ?? null,
                'invoice_no' => $item['invoice_no'] ?? null,
                'hotel_name' => $item['hotel_name'] ?? null,
                'description' => $item['description'] ?? null,
                'distance_km' => $distance,
                'mileage_rate' => $mileageRate,
                'mileage_amount' => $mileageAmount,
                'toll_amount' => $toll,
                'parking_amount' => $parking,
                'rate_amount' => $rateAmount,
                'quantity_value' => $quantity > 0 ? $quantity : 1,
                'amount' => $amount,
                'total_amount' => $totalAmount,
                'remarks' => $item['remarks'] ?? null,
            ];
        })->all();

        $totals = [
            'travelling' => round(collect($items)->where('category_code', 'travelling')->sum('total_amount'), 2),
            'transportation' => round(collect($items)->where('category_code', 'transportation')->sum('total_amount'), 2),
            'accommodation' => round(collect($items)->where('category_code', 'accommodation')->sum('total_amount'), 2),
            'travelling_allowance' => round(collect($items)->where('category_code', 'travelling_allowance')->sum('total_amount'), 2),
            'entertainment' => round(collect($items)->where('category_code', 'entertainment')->sum('total_amount'), 2),
            'miscellaneous' => round(collect($items)->where('category_code', 'miscellaneous')->sum('total_amount'), 2),
        ];

        $advance = max(0, (float) ($payload['advance_amount'] ?? 0));
        $grandTotal = round(array_sum($totals), 2);
        $balance = max(0, round($grandTotal - $advance, 2));

        return [$payload, $items, [
            'totals' => $totals,
            'advance_amount' => $advance,
            'grand_total' => $grandTotal,
            'balance_claim' => $balance,
        ]];
    }

    private function authorizeClaim(Request $request, Claim $claim, bool $editableOnly = false): void
    {
        $user = $request->user();

        if ($user->role !== 'admin' && $claim->employee_user_id !== $user->id) {
            abort(403, 'Forbidden');
        }

        if ($editableOnly && ! in_array($claim->status, ['draft', 'returned_for_amendment'], true)) {
            abort(422, 'This claim can no longer be edited.');
        }
    }

    private function persistItems(Claim $claim, array $items): void
    {
        foreach ($items as $item) {
            ClaimItem::create(array_merge($item, [
                'claim_id' => $claim->id,
            ]));
        }
    }

    private function logStatus(Claim $claim, ?string $from, string $to, string $action, int $userId, string $role, ?string $remarks): void
    {
        ClaimStatusLog::create([
            'claim_id' => $claim->id,
            'from_status' => $from,
            'to_status' => $to,
            'action_name' => $action,
            'action_by_user_id' => $userId,
            'action_role' => $role,
            'remarks' => $remarks,
        ]);
    }

    private function generateClaimNumber(string $claimMonth): string
    {
        $prefix = 'CLM-'.str_replace('-', '', $claimMonth).'-';
        $sequence = Claim::query()->where('claim_month', $claimMonth)->count() + 1;

        return $prefix.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);
    }

    private function transformClaim(Claim $claim): array
    {
        return [
            'id' => $claim->id,
            'claim_no' => $claim->claim_no,
            'employee_name' => $claim->employee_name,
            'employee_code' => $claim->employee_code,
            'position_title' => $claim->position_title,
            'department' => $claim->department,
            'cost_center' => $claim->cost_center,
            'claim_month' => $claim->claim_month,
            'claim_date' => optional($claim->claim_date)->toDateString(),
            'advance_amount' => (float) $claim->advance_amount,
            'grand_total' => (float) $claim->grand_total,
            'balance_claim' => (float) $claim->balance_claim,
            'employee_remarks' => $claim->employee_remarks,
            'manager_remarks' => $claim->manager_remarks,
            'finance_remarks' => $claim->finance_remarks,
            'status' => $claim->status,
            'attachment_count' => $claim->attachments?->count() ?? 0,
            'permissions' => [
                'can_edit' => in_array($claim->status, ['draft', 'returned_for_amendment'], true),
                'can_submit' => in_array($claim->status, ['draft', 'returned_for_amendment'], true),
                'can_upload' => in_array($claim->status, ['draft', 'returned_for_amendment'], true),
                'can_manager_review' => $claim->status === 'submitted',
                'can_finance_review' => $claim->status === 'pending_finance_verification',
                'can_mark_paid' => $claim->status === 'approved',
            ],
            'updated_at' => optional($claim->updated_at)->toDateTimeString(),
        ];
    }

    private function transformItem(ClaimItem $item): array
    {
        return [
            'id' => $item->id,
            'category_code' => $item->category?->code,
            'category_name' => $item->category?->name,
            'item_date' => optional($item->item_date)->toDateString(),
            'from_location' => $item->from_location ?? '',
            'to_location' => $item->to_location ?? '',
            'purpose' => $item->purpose ?? '',
            'receipt_no' => $item->receipt_no ?? '',
            'invoice_no' => $item->invoice_no ?? '',
            'hotel_name' => $item->hotel_name ?? '',
            'description' => $item->description ?? '',
            'distance_km' => (float) $item->distance_km,
            'mileage_rate' => (float) $item->mileage_rate,
            'mileage_amount' => (float) $item->mileage_amount,
            'toll_amount' => (float) $item->toll_amount,
            'parking_amount' => (float) $item->parking_amount,
            'rate_amount' => (float) $item->rate_amount,
            'quantity_value' => (float) $item->quantity_value,
            'amount' => (float) $item->amount,
            'total_amount' => (float) $item->total_amount,
            'remarks' => $item->remarks ?? '',
        ];
    }

    private function transformAttachment(ClaimAttachment $attachment): array
    {
        return [
            'id' => $attachment->id,
            'claim_id' => $attachment->claim_id,
            'file_name' => $attachment->file_name,
            'file_path' => $attachment->file_path,
            'mime_type' => $attachment->mime_type,
            'file_size' => (int) $attachment->file_size,
            'url' => Storage::disk('public')->url($attachment->file_path),
            'uploaded_at' => optional($attachment->created_at)->toDateTimeString(),
            'uploaded_by' => $attachment->uploader?->display_name,
        ];
    }

    private function transformPayment(ClaimPayment $payment): array
    {
        return [
            'id' => $payment->id,
            'payment_reference' => $payment->payment_reference,
            'payment_date' => optional($payment->payment_date)->toDateString(),
            'payment_method' => $payment->payment_method,
            'payment_amount' => (float) $payment->payment_amount,
            'remarks' => $payment->remarks,
            'recorded_by' => $payment->recorder?->display_name,
            'created_at' => optional($payment->created_at)->toDateTimeString(),
        ];
    }

    private function resolveActionTransition(string $role, string $status, string $action): array
    {
        $map = [
            'manager_approve' => [
                'allowed_role' => 'admin',
                'from_status' => 'submitted',
                'to_status' => 'pending_finance_verification',
                'target_field' => 'manager_remarks',
                'message' => 'Claim approved for finance verification.',
            ],
            'manager_reject' => [
                'allowed_role' => 'admin',
                'from_status' => 'submitted',
                'to_status' => 'rejected',
                'target_field' => 'manager_remarks',
                'message' => 'Claim rejected.',
            ],
            'manager_return' => [
                'allowed_role' => 'admin',
                'from_status' => 'submitted',
                'to_status' => 'returned_for_amendment',
                'target_field' => 'manager_remarks',
                'message' => 'Claim returned for amendment.',
            ],
            'finance_approve' => [
                'allowed_role' => 'admin',
                'from_status' => 'pending_finance_verification',
                'to_status' => 'approved',
                'target_field' => 'finance_remarks',
                'message' => 'Claim approved for payment.',
            ],
            'mark_paid' => [
                'allowed_role' => 'admin',
                'from_status' => 'approved',
                'to_status' => 'paid',
                'target_field' => 'finance_remarks',
                'message' => 'Claim marked as paid.',
            ],
        ];

        $transition = $map[$action] ?? null;
        abort_if(! $transition, 422, 'Invalid action.');
        abort_if($role !== $transition['allowed_role'], 403, 'Forbidden');
        abort_if($status !== $transition['from_status'], 422, 'Invalid claim status for this action.');

        return $transition;
    }
}
