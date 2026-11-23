<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationPreference extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'in_app',
        'sms',
        'email',
        'whatsapp',
    ];

    protected $casts = [
        'in_app' => 'boolean',
        'sms' => 'boolean',
        'email' => 'boolean',
        'whatsapp' => 'boolean',
    ];
}
