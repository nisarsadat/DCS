<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CureCycle extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = ['cure_id','appointment_id'];

    public function cure(){
        return $this->belongsTo(Cure::class);
    }

    public function scopeSearch($query, $search, $cure){
        if(!$search  && !$cure){
            return $query;
    }
    if($cure){
         return $query->where("cure_id",$cure);
    }

    return $query->whereHas('cure', function ($cureQuery) use ($search) {
        $cureQuery->where('reference', 'LIKE', '%' . $search . '%')
            ->orWhere('description', 'LIKE', '%' . $search . '%');
    });
}
}
