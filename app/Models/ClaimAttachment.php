<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClaimAttachment extends Model
{
    protected $fillable = [
        'claim_id',
        'file_name',
        'file_path',
        'mime_type',
        'file_size',
        'uploaded_by_user_id',
    ];

    public function claim(): BelongsTo
    {
        return $this->belongsTo(Claim::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }
}
