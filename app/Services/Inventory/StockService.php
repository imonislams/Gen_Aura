<?php

namespace App\Services\Inventory;

use App\Models\InventoryMovement;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class StockService
{
    public function apply(Product $product, int $delta, string $type, array $meta = []): InventoryMovement
    {
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
}
