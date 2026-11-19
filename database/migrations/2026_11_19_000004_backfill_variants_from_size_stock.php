<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('products')
            ->select('id', 'size_stock', 'stock')
            ->whereNotNull('size_stock')
            ->orderBy('id')
            ->chunkById(200, function ($products) {
                foreach ($products as $product) {
                    $map = is_string($product->size_stock)
                        ? json_decode($product->size_stock, true)
                        : (array) $product->size_stock;

                    if (!is_array($map) || $map === []) {
                        continue;
                    }

                    if (DB::table('product_variants')->where('product_id', $product->id)->exists()) {
                        continue;
                    }

                    $now = now();
                    $rows = [];
                    $total = 0;

                    foreach ($map as $size => $quantity) {
                        $quantity = max(0, (int) $quantity);
                        $rows[] = [
                            'product_id' => $product->id,
                            'color_id' => null,
                            'size' => (string) $size,
                            'sku' => null,
                            'barcode' => null,
                            'stock' => $quantity,
                            'status' => 'active',
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                        $total += $quantity;
                    }

                    if ($rows === []) {
                        continue;
                    }

                    DB::table('product_variants')->insert($rows);
                    DB::table('products')->where('id', $product->id)->update([
                        'has_variants' => true,
                        'stock' => $total,
                    ]);
                }
            });
    }

    public function down(): void
    {
        DB::table('product_variants')->whereNull('color_id')->delete();
        DB::table('products')->update(['has_variants' => false]);
    }
};
