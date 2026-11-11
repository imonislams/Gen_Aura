<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'slug', 'sku', 'cat', 'category_id', 'sub', 'sizes', 'feat', 'description',
        'price', 'old_price', 'stock', 'size_stock', 'status', 'featured', 'image', 'images',
    ];

    protected $casts = [
        'images' => 'array',
        'size_stock' => 'array',
        'featured' => 'boolean',
        'category_id' => 'integer',
        'price' => 'integer',
        'old_price' => 'integer',
        'stock' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (Product $product) {
            if (!empty($product->size_list) && is_array($product->size_stock) && count($product->size_stock) > 0) {
                $product->stock = $product->sizeStockTotal();
            }
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeFeatured($query)
    {
        return $query->where('featured', true);
    }

    public function getImageUrlAttribute(): string
    {
        return $this->image ? asset('storage/' . $this->image) : self::placeholder($this->name);
    }

    public function getGalleryAttribute(): array
    {
        return array_merge([$this->image_url], array_map(fn ($i) => asset('storage/' . $i), $this->images ?? []));
    }

    public function getSizeListAttribute(): array
    {
        return array_values(array_filter(array_map('trim', explode(',', (string) $this->sizes))));
    }

    public function getFeatListAttribute(): array
    {
        return array_values(array_filter(array_map('trim', explode(',', (string) $this->feat))));
    }

    public function hasSizeStock(): bool
    {
        return !empty($this->size_list) && is_array($this->size_stock) && count($this->size_stock) > 0;
    }

    public function stockForSize(string $size): ?int
    {
        if (!$this->hasSizeStock() || !array_key_exists($size, $this->size_stock)) {
            return null;
        }

        return (int) $this->size_stock[$size];
    }

    public function isSizeAvailable(string $size): bool
    {
        $stock = $this->stockForSize($size);

        return $stock === null ? (int) $this->stock > 0 : $stock > 0;
    }

    public function sizeStockTotal(): int
    {
        return (int) array_sum(array_map('intval', $this->size_stock ?? []));
    }

    public function getOffAttribute(): int
    {
        return $this->old_price > $this->price ? (int) round((1 - $this->price / $this->old_price) * 100) : 0;
    }

    public static function placeholder(string $t): string
    {
        $c = htmlspecialchars(mb_strtoupper(mb_substr($t, 0, 1)));
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 400"><defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#8E3FD0"/><stop offset="1" stop-color="#E3A04A"/></linearGradient></defs><rect width="400" height="400" fill="url(#g)"/><text x="200" y="240" font-size="130" text-anchor="middle" fill="#fff" font-family="serif">' . $c . '</text></svg>';

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
}
