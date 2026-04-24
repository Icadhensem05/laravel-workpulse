<?php

namespace App\Http\Controllers\AppApi;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'user' => $this->transformUser($request->user()),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $payload = Validator::make($request->all(), [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'employee_code' => ['nullable', 'string', 'max:100'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'cost_center' => ['nullable', 'string', 'max:255'],
            'base' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
        ])->validate();

        $user->safeFill([
            'name' => trim($payload['first_name'].' '.($payload['last_name'] ?? '')),
            'first_name' => $payload['first_name'],
            'last_name' => $payload['last_name'] ?? null,
            'email' => $payload['email'],
            'employee_code' => $payload['employee_code'] ?? null,
            'job_title' => $payload['job_title'] ?? null,
            'department' => $payload['department'] ?? null,
            'cost_center' => $payload['cost_center'] ?? null,
            'base' => $payload['base'] ?? null,
            'phone' => $payload['phone'] ?? null,
        ])->save();

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully.',
            'user' => $this->transformUser($user->fresh()),
        ]);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $user = $request->user();

        $payload = Validator::make($request->all(), [
            'current' => ['required', 'string'],
            'next' => ['required', 'string', 'min:8'],
        ])->validate();

        if (! Hash::check($payload['current'], (string) $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect.',
                'errors' => [
                    'current' => ['Current password is incorrect.'],
                ],
            ], 422);
        }

        $user->forceFill([
            'password' => $payload['next'],
        ])->save();

        return response()->json([
            'success' => true,
            'message' => 'Password updated successfully.',
        ]);
    }

    public function uploadPhoto(Request $request): JsonResponse
    {
        $user = $request->user();

        $payload = Validator::make($request->all(), [
            'photo' => ['required', 'image', 'max:2048'],
        ])->validate();

        if ($user->profile_photo && str_starts_with($user->profile_photo, 'profile-photos/')) {
            Storage::disk('public')->delete($user->profile_photo);
        }

        $path = $payload['photo']->store('profile-photos', 'public');
        $user->forceFill([
            'profile_photo' => $path,
        ])->save();

        return response()->json([
            'success' => true,
            'message' => 'Profile photo updated successfully.',
            'url' => Storage::disk('public')->url($path),
            'path' => $path,
            'user' => $this->transformUser($user->fresh()),
        ]);
    }

    private function transformUser($user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->display_name,
            'email' => $user->email,
            'role' => $user->role,
            'employee_code' => $user->employee_code,
            'job_title' => $user->job_title,
            'department' => $user->department,
            'cost_center' => $user->cost_center,
            'base' => $user->base,
            'phone' => $user->phone,
            'profile_photo' => $user->profile_photo ? Storage::disk('public')->url($user->profile_photo) : null,
        ];
    }
}
