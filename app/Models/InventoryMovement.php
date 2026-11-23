<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryMovement extends Model
{
    protected $fillable = [
        'inventory_item_id',
        'type',
        'quantity',
        'quantity_before',
        'quantity_after',
        'unit_cost',
        'note',
        'reference',
        'user_id',
        'dentist_id',
        'cure_id',
        'happened_at',
    ];

    protected $casts = [
        'quantity' => 'float',
        'quantity_before' => 'float',
        'quantity_after' => 'float',
        'unit_cost' => 'float',
        'happened_at' => 'datetime',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function dentist(): BelongsTo
    {
        return $this->belongsTo(People::class, 'dentist_id');
    }
}
