<?php

namespace App\Http\Controllers\AppApi;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AssetsController extends Controller
{
    public function index(): JsonResponse
    {
        $rows = Asset::query()
            ->with('assignee')
            ->latest('id')
            ->get()
            ->map(fn (Asset $asset) => $this->transformAsset($asset))
            ->values();

        return response()->json([
            'success' => true,
            'rows' => $rows,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        abort_if($request->user()->role !== 'admin', 403, 'Forbidden');

        $data = Validator::make($request->all(), [
            'asset_code' => ['required', 'string', 'max:100', 'unique:assets,asset_code'],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'serial_no' => ['nullable', 'string', 'max:120'],
            'remarks' => ['nullable', 'string'],
        ])->validate();

        $asset = Asset::create([
            'asset_code' => $data['asset_code'],
            'name' => $data['name'],
            'category' => $data['category'] ?? null,
            'serial_no' => $data['serial_no'] ?? null,
            'remarks' => $data['remarks'] ?? null,
            'status' => 'available',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Asset created successfully.',
            'row' => $this->transformAsset($asset->fresh('assignee')),
        ], 201);
    }

    public function updateStatus(Request $request, Asset $asset): JsonResponse
    {
        abort_if($request->user()->role !== 'admin', 403, 'Forbidden');

        $data = Validator::make($request->all(), [
            'status' => ['required', 'string', 'in:available,assigned,maintenance,returned'],
            'assigned_to_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'remarks' => ['nullable', 'string'],
        ])->validate();

        $asset->status = $data['status'];
        $asset->assigned_to_user_id = $data['status'] === 'assigned' ? ($data['assigned_to_user_id'] ?? null) : null;
        $asset->assigned_at = $data['status'] === 'assigned' && $data['assigned_to_user_id'] ? now()->toDateString() : null;
        $asset->remarks = $data['remarks'] ?? $asset->remarks;
        $asset->save();

        return response()->json([
            'success' => true,
            'message' => 'Asset status updated successfully.',
            'row' => $this->transformAsset($asset->fresh('assignee')),
        ]);
    }

    private function transformAsset(Asset $asset): array
    {
        return [
            'id' => $asset->id,
            'asset_code' => $asset->asset_code,
            'name' => $asset->name,
            'category' => $asset->category,
            'serial_no' => $asset->serial_no,
            'status' => $asset->status,
            'assigned_to_user_id' => $asset->assigned_to_user_id,
            'assigned_to_name' => $asset->assignee?->display_name,
            'assigned_at' => optional($asset->assigned_at)->toDateString(),
            'remarks' => $asset->remarks,
            'created_at' => optional($asset->created_at)->toDateTimeString(),
        ];
    }
}
