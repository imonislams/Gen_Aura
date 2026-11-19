<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductVariant extends Model
{
    protected $fillable = ['product_id', 'color_id', 'size', 'sku', 'barcode', 'stock', 'status'];

    protected $casts = [
        'product_id' => 'integer',
        'color_id' => 'integer',
        'stock' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function color(): BelongsTo
    {
        return $this->belongsTo(ProductColor::class, 'color_id');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'variant_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function isAvailable(): bool
    {
        if ($this->status !== 'active' || (int) $this->stock <= 0) {
            return false;
        }

        return $this->color_id === null
            || ($this->color !== null && $this->color->status === 'active');
    }

    public function getLabelAttribute(): string
    {
        $parts = array_filter([$this->color?->name, $this->size]);

        return $parts ? implode(' / ', $parts) : 'Default';
    }

    public function getImageUrlAttribute(): string
    {
        return $this->color?->image_url ?: $this->product?->image_url ?: Product::placeholder($this->product?->name ?? 'Product');
    }
}
