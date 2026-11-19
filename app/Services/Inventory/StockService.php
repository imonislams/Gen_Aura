<?php

namespace App\Services\Inventory;

use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class StockService
{
    public function applyVariant(ProductVariant $variant, int $delta, string $type, array $meta = []): InventoryMovement
    {
        $product = Product::withTrashed()->find($variant->product_id);
        $name = $product?->name ?: 'Product';
        $label = trim(($variant->color?->name ? $variant->color->name . ' / ' : '') . ($variant->size ?? ''), ' /');
        $suffix = $label !== '' ? ' (' . $label . ')' : '';

        $before = (int) $variant->stock;
        $after = $before + $delta;

        if ($after < 0) {
            throw new RuntimeException($name . $suffix . ': not enough stock.');
        }

        $stockBefore = (int) ($product?->stock ?? 0);

        $variant->stock = $after;
        $variant->save();

        if ($product) {
            $product->forceFill(['stock' => $this->activeVariantTotal($product->id)])->saveQuietly();
        }

        $stockAfter = $product ? (int) $product->fresh()->stock : 0;

        return InventoryMovement::create([
            'product_id' => $variant->product_id,
            'variant_id' => $variant->id,
            'type' => $type,
            'size' => $variant->size,
            'color' => $meta['color'] ?? $variant->color?->name,
            'quantity_delta' => $delta,
            'size_stock_before' => $before,
            'size_stock_after' => $after,
            'stock_before' => $stockBefore,
            'stock_after' => $stockAfter,
            'reference_type' => $meta['reference_type'] ?? null,
            'reference_id' => $meta['reference_id'] ?? null,
            'note' => $meta['note'] ?? null,
            'admin_id' => $meta['admin_id'] ?? null,
            'created_at' => now(),
        ]);
    }

    public function adjustVariant(ProductVariant $variant, int $delta, ?string $note, ?int $adminId): void
    {
        DB::transaction(function () use ($variant, $delta, $note, $adminId) {
            $locked = ProductVariant::lockForUpdate()->findOrFail($variant->id);

            $this->applyVariant($locked, $delta, 'manual_adjustment', [
                'note' => $note,
                'admin_id' => $adminId,
            ]);
        });
    }

    public function apply(Product $product, int $delta, string $type, array $meta = []): InventoryMovement
    {
        if ($product->has_variants) {
            throw new RuntimeException(($product->name ?: 'Product') . ': choose a variant.');
        }

        $size = $meta['size'] ?? null;
        $size = $size !== null && $size !== '' ? (string) $size : null;
        $before = (int) $product->stock;
        $sizeBefore = null;
        $sizeAfter = null;

        if ($product->hasSizeStock()) {
            if ($size === null) {
                throw new RuntimeException(($product->name ?: 'Product') . ': choose a size.');
            }

            $map = $product->size_stock;

            if (!array_key_exists($size, $map)) {
                if ($delta <= 0) {
                    throw new RuntimeException(($product->name ?: 'Product') . ': size ' . $size . ' is no longer available.');
                }

                $map[$size] = 0;
                $sizes = $product->size_list;

                if (!in_array($size, $sizes, true)) {
                    $sizes[] = $size;
                    $product->sizes = implode(',', $sizes);
                }
            }

            $sizeBefore = (int) $map[$size];
            $sizeAfter = $sizeBefore + $delta;

            if ($sizeAfter < 0) {
                throw new RuntimeException(($product->name ?: 'Product') . ' (' . $size . '): not enough stock.');
            }

            $map[$size] = $sizeAfter;
            $product->size_stock = $map;
            $product->stock = array_sum(array_map('intval', $map));
            $product->save();
        } else {
            $after = $before + $delta;

            if ($after < 0) {
                throw new RuntimeException(($product->name ?: 'Product') . ': not enough stock.');
            }

            $product->update(['stock' => $after]);
        }

        $afterTotal = (int) $product->fresh()->stock;

        return InventoryMovement::create([
            'product_id' => $product->id,
            'type' => $type,
            'size' => $size,
            'quantity_delta' => $delta,
            'size_stock_before' => $sizeBefore,
            'size_stock_after' => $sizeAfter,
            'stock_before' => $before,
            'stock_after' => $afterTotal,
            'reference_type' => $meta['reference_type'] ?? null,
            'reference_id' => $meta['reference_id'] ?? null,
            'note' => $meta['note'] ?? null,
            'admin_id' => $meta['admin_id'] ?? null,
            'created_at' => now(),
        ]);
    }

    public function adjust(Product $product, int $delta, ?string $note, ?int $adminId, ?string $size = null): void
    {
        DB::transaction(function () use ($product, $delta, $note, $adminId, $size) {
            $locked = Product::lockForUpdate()->findOrFail($product->id);

            $this->apply($locked, $delta, 'manual_adjustment', [
                'size' => $size,
                'note' => $note,
                'admin_id' => $adminId,
            ]);
        });
    }

    private function activeVariantTotal(int $productId): int
    {
        return (int) ProductVariant::where('product_id', $productId)
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('color_id')
                    ->orWhereHas('color', fn ($color) => $color->where('status', 'active'));
            })
            ->sum('stock');
    }
}
