<?php

namespace App\Http\Controllers\AppApi;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\ApprovalSetting;
use App\Models\Asset;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminController extends Controller
{
    public function users(Request $request): JsonResponse
    {
        abort_if($request->user()->role !== 'admin', 403, 'Forbidden');

        $rows = User::query()
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->display_name,
                'email' => $user->email,
                'role' => $user->role,
                'status' => $user->status,
                'department' => $user->department,
                'employee_code' => $user->employee_code,
            ])->values();

        return response()->json(['success' => true, 'rows' => $rows]);
    }

    public function approvals(Request $request): JsonResponse
    {
        abort_if($request->user()->role !== 'admin', 403, 'Forbidden');

        return response()->json([
            'success' => true,
            'rows' => ApprovalSetting::query()->orderBy('module')->orderBy('setting_key')->get()->map(fn (ApprovalSetting $setting) => [
                'id' => $setting->id,
                'module' => $setting->module,
                'setting_key' => $setting->setting_key,
                'setting_value' => $setting->setting_value,
            ])->values(),
        ]);
    }

    public function saveApprovals(Request $request): JsonResponse
    {
        abort_if($request->user()->role !== 'admin', 403, 'Forbidden');

        $data = Validator::make($request->all(), [
            'rows' => ['required', 'array'],
            'rows.*.module' => ['required', 'string', 'max:100'],
            'rows.*.setting_key' => ['required', 'string', 'max:120'],
            'rows.*.setting_value' => ['nullable', 'string', 'max:255'],
        ])->validate();

        foreach ($data['rows'] as $row) {
            ApprovalSetting::query()->updateOrCreate(
                ['module' => $row['module'], 'setting_key' => $row['setting_key']],
                ['setting_value' => $row['setting_value'] ?? null]
            );
        }

        return response()->json(['success' => true, 'message' => 'Approval settings saved successfully.']);
    }

    public function assets(Request $request): JsonResponse
    {
        abort_if($request->user()->role !== 'admin', 403, 'Forbidden');

        return response()->json([
            'success' => true,
            'rows' => Asset::query()->with('assignee')->latest('id')->get()->map(fn (Asset $asset) => [
                'id' => $asset->id,
                'asset_code' => $asset->asset_code,
                'name' => $asset->name,
                'status' => $asset->status,
                'assigned_to_name' => $asset->assignee?->display_name,
            ])->values(),
        ]);
    }

    public function settings(Request $request): JsonResponse
    {
        abort_if($request->user()->role !== 'admin', 403, 'Forbidden');

        return response()->json([
            'success' => true,
            'settings' => AppSetting::query()->pluck('setting_value', 'setting_key'),
        ]);
    }

    public function saveSettings(Request $request): JsonResponse
    {
        abort_if($request->user()->role !== 'admin', 403, 'Forbidden');

        $data = Validator::make($request->all(), [
            'settings' => ['required', 'array'],
        ])->validate();

        foreach ($data['settings'] as $key => $value) {
            AppSetting::query()->updateOrCreate(
                ['setting_key' => $key],
                ['setting_value' => is_scalar($value) || $value === null ? (string) $value : json_encode($value)]
            );
        }

        return response()->json(['success' => true, 'message' => 'Settings updated successfully.']);
    }

    public function linkPerson(Request $request): JsonResponse
    {
        abort_if($request->user()->role !== 'admin', 403, 'Forbidden');

        $data = Validator::make($request->all(), [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'employee_code' => ['required', 'string', 'max:100'],
        ])->validate();

        $user = User::query()->findOrFail($data['user_id']);
        $user->employee_code = $data['employee_code'];
        $user->save();

        return response()->json(['success' => true, 'message' => 'Person linked successfully.']);
    }

    public function overview(Request $request): JsonResponse
    {
        abort_if($request->user()->role !== 'admin', 403, 'Forbidden');

        return response()->json([
            'success' => true,
            'summary' => [
                'users_total' => User::query()->count(),
                'assets_total' => Asset::query()->count(),
                'assets_assigned' => Asset::query()->where('status', 'assigned')->count(),
                'settings_total' => AppSetting::query()->count(),
            ],
        ]);
    }
}
