<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryItem extends Model
{
    protected $fillable = [
        'name',
        'sku',
        'barcode',
        'inventory_category_id',
        'supplier_id',
        'unit',
        'quantity',
        'min_stock',
        'reorder_level',
        'purchase_price',
        'batch_number',
        'expires_at',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'quantity' => 'float',
        'min_stock' => 'float',
        'reorder_level' => 'float',
        'purchase_price' => 'float',
        'expires_at' => 'date',
        'is_active' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(InventoryCategory::class, 'inventory_category_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(People::class, 'supplier_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function stockStatus(): string
    {
        if ($this->quantity <= 0) {
            return 'out';
        }
        if ($this->quantity <= $this->min_stock) {
            return 'low';
        }

        return 'in';
    }
}
