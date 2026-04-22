<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceEntry extends Model
{
    protected $fillable = [
        'user_id',
        'attendance_date',
        'check_in_at',
        'check_out_at',
        'break_minutes',
        'total_minutes',
        'status',
        'remarks',
        'updated_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'attendance_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }
}
