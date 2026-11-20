<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessNotification extends Model
{
    protected $guarded = [];
    protected $casts = ['is_read' => 'boolean', 'data' => 'array'];
}
