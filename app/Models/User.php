<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Schema;

#[Fillable([
    'auth_user_id',
    'name',
    'first_name',
    'last_name',
    'email',
    'employee_code',
    'role',
    'status',
    'job_title',
    'department',
    'cost_center',
    'base',
    'phone',
    'profile_photo',
    'password',
    'password_hash',
    'last_login_at',
])]
#[Hidden(['password', 'password_hash', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected static function booted(): void
    {
        static::saving(function (self $user): void {
            $user->pruneMissingSchemaAttributes();
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'password_hash' => 'hashed',
            'last_login_at' => 'datetime',
        ];
    }

    public function getAuthPassword(): string
    {
        return (string) ($this->password_hash ?: $this->password);
    }

    public function getDisplayNameAttribute(): string
    {
        $full = trim(implode(' ', array_filter([
            $this->first_name,
            $this->last_name,
        ])));

        return $full !== '' ? $full : (string) $this->name;
    }

    public function safeFill(array $attributes): static
    {
        $columns = $this->schemaColumns();
        $filtered = array_intersect_key($attributes, array_flip($columns));
        $this->fill($filtered);

        return $this;
    }

    private function pruneMissingSchemaAttributes(): void
    {
        $columns = array_flip($this->schemaColumns());
        foreach (array_keys($this->getAttributes()) as $key) {
            if (! isset($columns[$key])) {
                unset($this->{$key});
            }
        }
    }

    private function schemaColumns(): array
    {
        static $cache = [];
        $table = $this->getTable();

        if (! isset($cache[$table])) {
            $cache[$table] = Schema::getColumnListing($table);
        }

        return $cache[$table];
    }
}
