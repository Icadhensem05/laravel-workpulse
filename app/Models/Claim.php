<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Claim extends Model
{
    protected $fillable = [
        'claim_no',
        'employee_user_id',
        'company_name',
        'employee_name',
        'employee_code',
        'position_title',
        'department',
        'cost_center',
        'claim_month',
        'claim_date',
        'total_travelling',
        'total_transportation',
        'total_accommodation',
        'total_travelling_allowance',
        'total_entertainment',
        'total_miscellaneous',
        'advance_amount',
        'grand_total',
        'balance_claim',
        'employee_remarks',
        'manager_remarks',
        'finance_remarks',
        'status',
        'submitted_at',
        'approved_at',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'claim_date' => 'date',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ClaimItem::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ClaimStatusLog::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ClaimAttachment::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(ClaimPayment::class);
    }
}
