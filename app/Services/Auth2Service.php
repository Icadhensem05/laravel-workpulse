<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class Auth2Service
{
    public function login(string $email, string $password): array
    {
        return $this->request('post', '/api/auth_login.php', [
            'email' => $email,
            'password' => $password,
            'site_key' => $this->siteKey(),
        ]);
    }

    public function requestPasswordReset(string $email, string $resetUrlBase): array
    {
        return $this->request('post', '/api/auth_password_reset_request.php', [
            'email' => $email,
            'reset_url_base' => $resetUrlBase,
        ]);
    }

    public function checkPasswordResetToken(string $token): array
    {
        return $this->request('get', '/api/auth_password_reset_check.php?token='.urlencode($token));
    }

    public function completePasswordReset(string $token, string $password): array
    {
        return $this->request('post', '/api/auth_password_reset_complete.php', [
            'token' => $token,
            'password' => $password,
        ]);
    }

    public function syncUser(array $authUser): User
    {
        $authUserId = (int) ($authUser['id'] ?? 0);
        $email = filter_var(trim((string) ($authUser['email'] ?? '')), FILTER_VALIDATE_EMAIL);
        $firstName = trim((string) ($authUser['first_name'] ?? ''));
        $lastName = trim((string) ($authUser['last_name'] ?? ''));
        $status = strtolower(trim((string) ($authUser['status'] ?? 'active')));

        abort_if($authUserId <= 0 || ! $email || $firstName === '', 500, 'Invalid Auth2 user payload.');

        $user = User::query()
            ->where('auth_user_id', $authUserId)
            ->first();

        if (! $user) {
            $user = User::query()
                ->where('email', $email)
                ->first();
        }

        $attributes = [
            'auth_user_id' => $authUserId,
            'name' => trim($firstName.' '.$lastName),
            'first_name' => $firstName,
            'last_name' => $lastName !== '' ? $lastName : null,
            'email' => $email,
            'status' => $status === 'active' ? 'active' : 'suspended',
        ];

        if ($user) {
            $user->fill($attributes);
        } else {
            $user = new User($attributes + [
                'role' => 'employee',
                'password' => Str::random(40),
            ]);
        }

        $user->save();

        return $user->fresh();
    }

    private function request(string $method, string $path, array $payload = []): array
    {
        $response = $method === 'get'
            ? $this->client()->get($path)
            : $this->client()->send(strtoupper($method), $path, ['json' => $payload]);

        $raw = (string) $response->body();
        $json = $this->decodeJson($raw);

        if (! is_array($json)) {
            Log::warning('Auth2 returned a non-JSON payload.', [
                'status' => $response->status(),
                'path' => $path,
            ]);
        }

        return [
            'ok' => $response->successful(),
            'status' => $response->status(),
            'json' => $json,
            'raw' => $raw,
        ];
    }

    private function client(): PendingRequest
    {
        return Http::acceptJson()
            ->asJson()
            ->timeout(20)
            ->connectTimeout(10)
            ->baseUrl(rtrim((string) config('services.auth2.base_url'), '/'));
    }

    private function siteKey(): string
    {
        return trim((string) config('services.auth2.site_key', 'wesb')) ?: 'wesb';
    }

    private function decodeJson(string $body): ?array
    {
        $body = preg_replace('/^\xEF\xBB\xBF/', '', $body) ?? $body;
        $body = ltrim($body);
        $decoded = json_decode($body, true);

        return is_array($decoded) ? $decoded : null;
    }
}
