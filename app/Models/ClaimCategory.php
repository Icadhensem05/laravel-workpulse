<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClaimCategory extends Model
{
    protected $fillable = [
        'code',
        'name',
        'requires_attachment',
        'is_active',
        'display_order',
    ];
}
