<?php

namespace App\Support\Store;

use App\Models\Product;
use App\Models\Setting;

class ProductPresenter
{
    public static function card(Product $product): array
    {
        $threshold = (int) (Setting::all_()['low_stock_threshold'] ?? 5);

        if ($product->has_variants) {
            $activeVariants = $product->variants->filter(function ($variant) {
                if ($variant->status !== 'active') {
                    return false;
                }

                return $variant->color_id === null
                    || ($variant->color !== null && $variant->color->status === 'active');
            })->values();

            $variants = $activeVariants->map(function ($variant) use ($threshold) {
                $available = $variant->status === 'active' && (int) $variant->stock > 0;

                return [
                    'id' => $variant->id,
                    'color_id' => $variant->color_id,
                    'color' => $variant->color?->name,
                    'color_hex' => $variant->color?->hex,
                    'size' => $variant->size,
                    'stock' => (int) $variant->stock,
                    'sku' => $variant->sku,
                    'available' => $available,
                    'low' => $available && (int) $variant->stock <= $threshold,
                    'sold_out' => !$available,
                    'image_url' => $variant->image_url,
                ];
            })->values();

            $colors = $product->colors
                ->where('status', 'active')
                ->values()
                ->map(function ($color) use ($variants) {
                    return [
                        'id' => $color->id,
                        'name' => $color->name,
                        'hex' => $color->hex,
                        'image_url' => $color->image_url,
                        'images' => $color->image_urls,
                        'available' => $variants->contains(fn ($variant) => $variant['color_id'] === $color->id && $variant['available']),
                    ];
                });

            $sizes = $variants->pluck('size')->filter()->unique()->values();
            $default = $variants->firstWhere('available', true);

            return [
                'id' => $product->id,
                'name' => $product->name,
                'cat' => $product->cat,
                'category' => $product->category?->name,
                'description' => $product->description,
                'price' => $product->price,
                'old_price' => $product->old_price,
                'stock' => $product->stock,
                'off' => $product->off,
                'has_variants' => true,
                'sizes' => $sizes,
                'size_options' => [],
                'colors' => $colors,
                'variants' => $variants,
                'default_variant_id' => $default['id'] ?? null,
                'low_stock_threshold' => $threshold,
                'image_url' => $product->image_url,
                'url' => route('product', $product),
            ];
        }

        $hasSizeStock = $product->hasSizeStock();

        $sizeOptions = collect($product->size_list)->map(function (string $size) use ($product, $hasSizeStock, $threshold) {
            $stock = $hasSizeStock ? (int) ($product->size_stock[$size] ?? 0) : null;
            $available = $hasSizeStock ? $stock > 0 : (int) $product->stock > 0;
            $low = $hasSizeStock
                ? ($stock > 0 && $stock <= $threshold)
                : ((int) $product->stock > 0 && (int) $product->stock <= $threshold);

            return [
                'name' => $size,
                'stock' => $stock,
                'available' => $available,
                'low' => $low,
                'sold_out' => !$available,
            ];
        })->values();

        return [
            'id' => $product->id,
            'name' => $product->name,
            'cat' => $product->cat,
            'category' => $product->category?->name,
            'description' => $product->description,
            'price' => $product->price,
            'old_price' => $product->old_price,
            'stock' => $product->stock,
            'off' => $product->off,
            'has_variants' => false,
            'sizes' => $product->size_list,
            'size_options' => $sizeOptions,
            'colors' => [],
            'variants' => [],
            'default_variant_id' => null,
            'low_stock_threshold' => $threshold,
            'image_url' => $product->image_url,
            'url' => route('product', $product),
        ];
    }

    public static function detail(Product $product): array
    {
        return self::card($product) + [
            'sub' => $product->sub,
            'features' => $product->feat_list,
            'gallery' => $product->gallery,
            'status' => $product->status,
        ];
    }
}
