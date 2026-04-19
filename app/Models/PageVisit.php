<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PageVisit extends Model
{
    public $timestamps = false;

    protected $fillable = ['url', 'ip_hash', 'referer', 'user_agent', 'created_at'];

    protected $casts = [
        'created_at' => 'datetime',
    ];
}
