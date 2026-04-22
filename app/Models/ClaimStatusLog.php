<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClaimStatusLog extends Model
{
    protected $fillable = [
        'claim_id',
        'from_status',
        'to_status',
        'action_name',
        'action_by_user_id',
        'action_role',
        'remarks',
    ];

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'action_by_user_id');
    }
}
