<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Asset extends Model
{
    protected $fillable = [
        'asset_code',
        'name',
        'category',
        'serial_no',
        'status',
        'assigned_to_user_id',
        'assigned_at',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'date',
        ];
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }
}
