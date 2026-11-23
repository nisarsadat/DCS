<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ToothImage extends Model
{
    protected $fillable = ['tooth_id', 'path'];

    public function tooth()
    {
        return $this->belongsTo(Tooth::class);
    }
}
