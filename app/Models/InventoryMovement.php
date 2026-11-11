<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryMovement extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'product_id', 'type', 'size', 'quantity_delta', 'size_stock_before', 'size_stock_after',
        'stock_before', 'stock_after', 'reference_type', 'reference_id', 'note', 'admin_id', 'created_at',
    ];

    protected $casts = [
        'quantity_delta' => 'integer',
        'size_stock_before' => 'integer',
        'size_stock_after' => 'integer',
        'stock_before' => 'integer',
        'stock_after' => 'integer',
        'created_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
