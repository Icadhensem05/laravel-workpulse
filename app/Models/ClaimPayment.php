<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClaimPayment extends Model
{
    protected $fillable = [
        'claim_id',
        'payment_reference',
        'payment_date',
        'payment_method',
        'payment_amount',
        'remarks',
        'recorded_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
        ];
    }

    public function claim(): BelongsTo
    {
        return $this->belongsTo(Claim::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }
}
