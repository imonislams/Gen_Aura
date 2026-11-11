<?php

namespace App\Support\Store;

use App\Models\Product;
use App\Models\Setting;

class ProductPresenter
{
    public static function card(Product $product): array
    {
        $threshold = (int) (Setting::all_()['low_stock_threshold'] ?? 5);
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
            'sizes' => $product->size_list,
            'size_options' => $sizeOptions,
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
