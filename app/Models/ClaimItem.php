<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClaimItem extends Model
{
    protected $fillable = [
        'claim_id',
        'category_id',
        'line_no',
        'item_date',
        'from_location',
        'to_location',
        'purpose',
        'receipt_no',
        'invoice_no',
        'hotel_name',
        'description',
        'distance_km',
        'mileage_rate',
        'mileage_amount',
        'toll_amount',
        'parking_amount',
        'rate_amount',
        'quantity_value',
        'amount',
        'total_amount',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'item_date' => 'date',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ClaimCategory::class, 'category_id');
    }
}
