<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveType extends Model
{
    protected $fillable = [
        'code',
        'name',
        'default_days',
        'is_active',
        'display_order',
    ];

    public function allocations(): HasMany
    {
        return $this->hasMany(LeaveAllocation::class);
    }

    public function requests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }
}
