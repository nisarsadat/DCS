<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationState extends Model
{
    protected $fillable = ['user_id', 'source_key', 'read_at'];

    protected $casts = ['read_at' => 'datetime'];
}
