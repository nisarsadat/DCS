<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationDispatch extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'source_key',
        'channel',
        'status',
        'detail',
        'sent_on',
        'created_at',
    ];

    protected $casts = [
        'sent_on' => 'date',
        'created_at' => 'datetime',
    ];
}
