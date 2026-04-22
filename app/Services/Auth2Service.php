<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
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
        $this->ensureUserSchema();

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

        $attributes = array_filter([
            'auth_user_id' => $authUserId,
            'name' => $this->hasUserColumn('name') ? trim($firstName.' '.$lastName) : null,
            'first_name' => $this->hasUserColumn('first_name') ? $firstName : null,
            'last_name' => $this->hasUserColumn('last_name') ? ($lastName !== '' ? $lastName : null) : null,
            'email' => $email,
            'status' => $this->hasUserColumn('status') ? ($status === 'active' ? 'active' : 'suspended') : null,
        ], static fn ($value) => $value !== null);

        if ($user) {
            $user->fill($attributes);
        } else {
            $createAttributes = $attributes + [
                'role' => 'employee',
            ];

            if ($this->hasUserColumn('password')) {
                $createAttributes['password'] = Str::random(40);
            }

            if ($this->hasUserColumn('password_hash')) {
                $createAttributes['password_hash'] = password_hash(Str::random(40), PASSWORD_DEFAULT);
            }

            $user = new User($createAttributes);
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

    private function ensureUserSchema(): void
    {
        if (! $this->hasUserColumn('auth_user_id')) {
            DB::statement('ALTER TABLE users ADD COLUMN auth_user_id BIGINT UNSIGNED NULL');
        }

        if (! $this->hasUserColumn('last_login_at')) {
            DB::statement('ALTER TABLE users ADD COLUMN last_login_at DATETIME NULL');
        }
    }

    private function hasUserColumn(string $column): bool
    {
        return Schema::hasColumn('users', $column);
    }
}
