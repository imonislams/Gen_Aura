<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductColor extends Model
{
    protected $fillable = ['product_id', 'name', 'hex', 'image', 'images', 'sort_order', 'status'];

    protected $casts = [
        'product_id' => 'integer',
        'sort_order' => 'integer',
        'images' => 'array',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class, 'color_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function getImageListAttribute(): array
    {
        $images = $this->images ?? [];

        if ($this->image) {
            array_unshift($images, $this->image);
        }

        return array_values(array_unique(array_filter(
            array_map(fn ($path) => is_string($path) ? trim($path) : '', $images),
            fn ($path) => $path !== ''
        )));
    }

    public function getImageUrlsAttribute(): array
    {
        return array_map(fn ($path) => asset('storage/' . $path), $this->image_list);
    }

    public function getImageUrlAttribute(): ?string
    {
        $first = $this->image_list[0] ?? null;

        return $first ? asset('storage/' . $first) : null;
    }
}
