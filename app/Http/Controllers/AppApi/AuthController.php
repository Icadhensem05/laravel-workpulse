<?php

namespace App\Http\Controllers\AppApi;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth2Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Throwable;

class AuthController extends Controller
{
    public function __construct(private readonly Auth2Service $auth2)
    {
    }

    public function login(Request $request): JsonResponse
    {
        $credentials = Validator::make($request->all(), [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ])->validate();

        $remote = $this->auth2->login($credentials['email'], $credentials['password']);
        $payload = $remote['json'] ?? null;

        if (! is_array($payload)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid Auth2 response.',
            ], 502);
        }

        if (($payload['ok'] ?? false) !== true) {
            $status = (int) (($remote['status'] ?? 422) ?: 422);

            return response()->json([
                'success' => false,
                'message' => (string) ($payload['error'] ?? 'The provided credentials are incorrect.'),
                'errors' => [
                    'email' => [(string) ($payload['error'] ?? 'The provided credentials are incorrect.')],
                ],
            ], $status >= 400 ? $status : 422);
        }

        $authUser = $payload['user'] ?? null;
        if (! is_array($authUser)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid Auth2 user payload.',
            ], 502);
        }

        if (strtolower((string) ($authUser['status'] ?? 'active')) !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Account is not active.',
            ], 403);
        }

        $user = $this->auth2->syncUser($authUser);
        $user->forceFill([
            'last_login_at' => now(),
        ])->save();

        Auth::login($user, (bool) $request->boolean('remember'));
        $request->session()->regenerate();

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'user' => $this->transformUser($user->fresh()),
        ]);
    }

    public function register(Request $request): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Registration is managed centrally by Auth2 admin.',
        ], 403);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        return response()->json([
            'success' => true,
            'user' => $this->transformUser($user),
        ]);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $payload = Validator::make($request->all(), [
            'email' => ['required', 'email'],
        ])->validate();

        try {
            $this->auth2->requestPasswordReset(
                $payload['email'],
                url('/reset-password')
            );
        } catch (Throwable) {
            // Keep the response enumeration-safe and consistent with the live bridge.
        }

        return response()->json([
            'success' => true,
            'message' => 'If the account exists, a reset link has been sent.',
        ]);
    }

    public function checkResetToken(Request $request): JsonResponse
    {
        $token = (string) $request->query('token', '');

        if ($token === '') {
            return response()->json([
                'success' => false,
                'message' => 'Reset token is missing.',
            ], 422);
        }

        $remote = $this->auth2->checkPasswordResetToken($token);
        $payload = $remote['json'] ?? null;
        if (! is_array($payload)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid Auth2 response.',
            ], 502);
        }

        if (($payload['ok'] ?? false) !== true) {
            return response()->json([
                'success' => false,
                'message' => (string) ($payload['error'] ?? 'Reset token is invalid.'),
            ], (int) (($remote['status'] ?? 422) ?: 422));
        }

        return response()->json([
            'success' => true,
            'message' => 'Reset token verified.',
            'data' => [
                'token' => $token,
            ],
        ]);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $payload = Validator::make($request->all(), [
            'token' => ['required', 'string'],
            'password' => ['required', 'confirmed', 'min:8'],
        ])->validate();

        $remote = $this->auth2->completePasswordReset($payload['token'], $payload['password']);
        $remotePayload = $remote['json'] ?? null;

        if (! is_array($remotePayload)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid Auth2 response.',
            ], 502);
        }

        if (($remotePayload['ok'] ?? false) !== true) {
            return response()->json([
                'success' => false,
                'message' => (string) ($remotePayload['error'] ?? 'Password reset failed.'),
            ], (int) (($remote['status'] ?? 422) ?: 422));
        }

        return response()->json([
            'success' => true,
            'message' => 'Password reset complete.',
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'success' => true,
            'message' => 'Signed out successfully.',
        ]);
    }

    private function transformUser(?User $user): ?array
    {
        if (! $user) {
            return null;
        }

        return [
            'id' => $user->id,
            'auth_user_id' => $user->auth_user_id,
            'name' => $user->display_name,
            'email' => $user->email,
            'role' => $user->role,
            'status' => $user->status,
            'employee_code' => $user->employee_code,
            'job_title' => $user->job_title,
            'department' => $user->department,
            'cost_center' => $user->cost_center,
            'base' => $user->base,
            'phone' => $user->phone,
            'profile_photo' => $user->profile_photo,
        ];
    }
}
