<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApprovalSetting extends Model
{
    protected $fillable = [
        'module',
        'setting_key',
        'setting_value',
    ];
}
