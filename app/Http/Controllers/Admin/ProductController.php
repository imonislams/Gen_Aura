<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductColor;
use App\Models\ProductVariant;
use App\Services\Inventory\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class ProductController extends Controller
{
    public function index(Request $r)
    {
        $q = Product::with('category');

        if ($r->filled('q')) {
            $q->where(fn ($x) => $x->where('name', 'like', '%' . $r->q . '%')->orWhere('sku', 'like', '%' . $r->q . '%'));
        }
        if ($r->boolean('low')) {
            $q->where('stock', '<=', 3);
        }
        if ($r->filled('status')) {
            $q->where('status', $r->status);
        }
        if ($r->boolean('trashed')) {
            $q->onlyTrashed();
        }

        return Inertia::render('Admin/Products/Index', [
            'products' => $q->latest()->paginate(30)->withQueryString()->through(fn (Product $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'slug' => $p->slug,
                'sku' => $p->sku,
                'category' => $p->category?->name ?? ($p->cat ?: 'Uncategorised'),
                'image_url' => $p->image_url,
                'price' => $p->price,
                'stock' => $p->stock,
                'status' => $p->status,
                'featured' => $p->featured,
                'trashed' => $p->trashed(),
                'deleted_at' => $p->deleted_at?->toIso8601String(),
            ]),
            'filters' => $r->only(['q', 'status', 'low', 'trashed']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Products/Form', [
            'product' => null,
            'categories' => Category::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function edit(Product $product)
    {
        return Inertia::render('Admin/Products/Form', [
            'product' => $this->formProduct($product),
            'categories' => Category::orderBy('name')->get(['id', 'name']),
        ]);
    }

    private function formProduct(Product $product): array
    {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'sku' => $product->sku,
            'category_id' => $product->category_id,
            'cat' => $product->cat,
            'sub' => $product->sub,
            'sizes' => $product->sizes,
            'feat' => $product->feat,
            'description' => $product->description,
            'price' => $product->price,
            'old_price' => $product->old_price,
            'stock' => $product->stock,
            'size_stock' => $product->size_stock ?? [],
            'has_variants' => (bool) $product->has_variants,
            'colors' => $product->colors()->get()->map(fn (ProductColor $color) => [
                'id' => $color->id,
                'key' => (string) $color->id,
                'name' => $color->name,
                'hex' => $color->hex,
                'image_url' => $color->image_url,
                'images' => $color->image_list,
                'status' => $color->status,
            ])->values(),
            'variants' => $product->variants()->withCount('orderItems')->get()->map(fn (ProductVariant $variant) => [
                'id' => $variant->id,
                'color_key' => $variant->color_id ? (string) $variant->color_id : '',
                'size' => $variant->size,
                'stock' => $variant->stock,
                'sku' => $variant->sku,
                'barcode' => $variant->barcode,
                'status' => $variant->status,
                'has_sales' => $variant->order_items_count > 0,
            ])->values(),
            'status' => $product->status,
            'featured' => (bool) $product->featured,
            'image_url' => $product->image_url,
            'images' => $product->images ?? [],
        ];
    }

    public function store(Request $r)
    {
        $variantData = $this->variantPayload($r);
        $product = DB::transaction(function () use ($r, $variantData) {
            $product = Product::create($this->payload($r, new Product()));

            if ($variantData !== null) {
                $product->forceFill(['stock' => 0])->saveQuietly();
                $this->syncVariantConfiguration($product, $variantData);
                $product->syncVariantStock();

                return $product;
            }

            if ($product->hasSizeStock()) {
                $running = 0;

                foreach ($product->size_stock as $size => $qty) {
                    $qty = (int) $qty;

                    if ($qty <= 0) {
                        continue;
                    }

                    InventoryMovement::create([
                        'product_id' => $product->id,
                        'type' => 'initial_stock',
                        'size' => $size,
                        'quantity_delta' => $qty,
                        'size_stock_before' => 0,
                        'size_stock_after' => $qty,
                        'stock_before' => $running,
                        'stock_after' => $running + $qty,
                        'note' => 'Product created',
                        'admin_id' => session('admin_id'),
                        'created_at' => now(),
                    ]);

                    $running += $qty;
                }
            } elseif ((int) $product->stock > 0) {
                InventoryMovement::create([
                    'product_id' => $product->id,
                    'type' => 'initial_stock',
                    'quantity_delta' => (int) $product->stock,
                    'stock_before' => 0,
                    'stock_after' => (int) $product->stock,
                    'note' => 'Product created',
                    'admin_id' => session('admin_id'),
                    'created_at' => now(),
                ]);
            }

            return $product;
        });

        return redirect()->route('admin.products.index')->with('ok', 'Product added');
    }

    public function update(Request $r, Product $product)
    {
        $variantData = $this->variantPayload($r);
        $oldStock = (int) $product->stock;
        $oldSizeStock = $product->size_stock;

        DB::transaction(function () use ($r, $product, $variantData, $oldStock, $oldSizeStock) {
            $product->update($this->payload($r, $product));

            if ($variantData !== null) {
                $this->syncVariantConfiguration($product, $variantData);
                $product->syncVariantStock();

                return;
            }

            if ($product->hasSizeStock() && is_array($oldSizeStock) && count($oldSizeStock) > 0) {
                $running = $oldStock;
                $sizes = array_unique(array_merge(array_keys($oldSizeStock), array_keys($product->size_stock)));
                sort($sizes);

                foreach ($sizes as $size) {
                    $beforeSize = (int) ($oldSizeStock[$size] ?? 0);
                    $afterSize = (int) ($product->size_stock[$size] ?? 0);
                    $delta = $afterSize - $beforeSize;

                    if ($delta === 0) {
                        continue;
                    }

                    InventoryMovement::create([
                        'product_id' => $product->id,
                        'type' => 'manual_adjustment',
                        'size' => $size,
                        'quantity_delta' => $delta,
                        'size_stock_before' => $beforeSize,
                        'size_stock_after' => $afterSize,
                        'stock_before' => $running,
                        'stock_after' => $running + $delta,
                        'note' => 'Admin product edit',
                        'admin_id' => session('admin_id'),
                        'created_at' => now(),
                    ]);

                    $running += $delta;
                }
            } elseif ((int) $product->stock !== $oldStock) {
                InventoryMovement::create([
                    'product_id' => $product->id,
                    'type' => 'manual_adjustment',
                    'quantity_delta' => (int) $product->stock - $oldStock,
                    'stock_before' => $oldStock,
                    'stock_after' => (int) $product->stock,
                    'note' => 'Admin product edit',
                    'admin_id' => session('admin_id'),
                    'created_at' => now(),
                ]);
            }
        });

        return redirect()->route('admin.products.index')->with('ok', 'Product updated');
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return back()->with('ok', 'Product deleted (it can be restored)');
    }

    public function restore(Product $product)
    {
        $product->restore();

        return back()->with('ok', 'Product restored');
    }

    private function payload(Request $r, Product $p): array
    {
        $d = $r->validate([
            'name' => 'required|max:120',
            'slug' => ['nullable', 'max:140', Rule::unique('products', 'slug')->ignore($p->id)],
            'sku' => ['nullable', 'max:60', Rule::unique('products', 'sku')->ignore($p->id)],
            'category_id' => ['nullable', 'exists:categories,id'],
            'cat' => 'nullable|max:40',
            'sub' => 'nullable|max:80',
            'sizes' => 'nullable|max:80',
            'feat' => 'nullable|max:200',
            'description' => 'nullable|max:2000',
            'price' => 'required|integer|min:0',
            'old_price' => 'nullable|integer|min:0',
            'stock' => 'required|integer|min:0',
            'size_stock' => 'nullable|array',
            'size_stock.*' => 'integer|min:0',
            'has_variants' => 'nullable|boolean',
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'featured' => 'nullable|boolean',
            'image' => 'nullable|image|max:4096',
            'images' => 'nullable|array',
            'images.*' => 'image|max:4096',
            'remove_images' => 'nullable|array',
            'remove_images.*' => 'string',
        ]);

        unset($d['image'], $d['images'], $d['remove_images']);

        $d['old_price'] = $d['old_price'] ?? 0;
        $d['featured'] = (bool) ($d['featured'] ?? false);
        $d['status'] = $d['status'] ?? 'active';
        $d['has_variants'] = (bool) ($d['has_variants'] ?? false);

        $sizeList = array_values(array_unique(array_filter(array_map('trim', explode(',', (string) ($d['sizes'] ?? ''))))));
        $d['sizes'] = $sizeList ? implode(',', $sizeList) : null;

        if ($d['has_variants']) {
            $d['size_stock'] = null;
        } elseif (!empty($sizeList)) {
            $sizeStock = $d['size_stock'] ?? null;

            if (is_array($sizeStock) && count($sizeStock) > 0) {
                $clean = [];

                foreach ($sizeList as $size) {
                    $clean[$size] = max(0, (int) ($sizeStock[$size] ?? 0));
                }

                $d['size_stock'] = $clean;
                $d['stock'] = array_sum($clean);
            } else {
                $d['size_stock'] = null;
            }
        } else {
            $d['size_stock'] = null;
        }

        if (array_key_exists('category_id', $d)) {
            if (!empty($d['category_id'])) {
                $category = Category::find($d['category_id']);
                $d['cat'] = $category?->name;
            } else {
                $d['cat'] = null;
            }
        }

        $d['slug'] = !empty($d['slug'] ?? null)
            ? (Str::slug($d['slug']) ?: $this->uniqueSlug($d['name'], $p->id))
            : $this->uniqueSlug($d['name'], $p->id);

        if (empty($d['sku'] ?? null)) {
            $d['sku'] = null;
        }

        $d['image'] = $p->image;
        if ($r->hasFile('image')) {
            if ($p->image) {
                Storage::disk('public')->delete($p->image);
            }
            $d['image'] = $r->file('image')->store('products', 'public');
        }

        $keep = array_values(array_diff($p->images ?? [], $r->input('remove_images', [])));
        foreach (array_diff($p->images ?? [], $keep) as $f) {
            Storage::disk('public')->delete($f);
        }
        $newImages = $r->file('images', []);
        $newImages = $newImages instanceof \Illuminate\Http\UploadedFile ? [$newImages] : (array) $newImages;

        foreach ($newImages as $f) {
            if ($f instanceof \Illuminate\Http\UploadedFile) {
                $keep[] = $f->store('products', 'public');
            }
        }
        $d['images'] = $keep;

        return $d;
    }

    private function variantPayload(Request $r): ?array
    {
        if (!$r->boolean('has_variants')) {
            return null;
        }

        $d = $r->validate([
            'colors' => 'nullable|array',
            'colors.*.key' => 'required|string|max:40',
            'colors.*.id' => 'nullable|integer',
            'colors.*.name' => 'nullable|string|max:60',
            'colors.*.hex' => 'nullable|string|max:7',
            'colors.*.image' => 'nullable|image|max:4096',
            'colors.*.images' => 'nullable|array',
            'colors.*.images.*' => 'string',
            'colors.*.new_images' => 'nullable|array',
            'colors.*.new_images.*' => 'image|max:4096',
            'colors.*.remove_image' => 'nullable|boolean',
            'colors.*.status' => 'nullable|in:active,disabled',
            'variants' => 'nullable|array',
            'variants.*.id' => 'nullable|integer',
            'variants.*.color_key' => 'nullable|string|max:40',
            'variants.*.size' => 'nullable|string|max:20',
            'variants.*.stock' => 'required|integer|min:0',
            'variants.*.sku' => 'nullable|string|max:60',
            'variants.*.barcode' => 'nullable|string|max:80',
            'variants.*.status' => 'required|in:active,disabled',
            'removed_variant_ids' => 'nullable|array',
            'removed_variant_ids.*' => 'integer',
        ]);

        $variants = $d['variants'] ?? [];
        $usedKeys = collect($variants)
            ->pluck('color_key')
            ->filter(fn ($key) => $key !== null && $key !== '')
            ->map(fn ($key) => (string) $key)
            ->all();

        $colors = array_values(array_filter($d['colors'] ?? [], function ($color) use ($usedKeys) {
            $name = trim((string) ($color['name'] ?? ''));
            $key = (string) ($color['key'] ?? '');

            return !empty($color['id']) || $name !== '' || in_array($key, $usedKeys, true);
        }));

        foreach ($colors as $index => $color) {
            if (($color['status'] ?? 'active') === 'disabled') {
                continue;
            }

            if (trim((string) ($color['name'] ?? '')) === '') {
                throw ValidationException::withMessages(["colors.$index.name" => 'Color name is required.']);
            }
        }

        $names = array_values(array_filter(array_map(
            fn ($color) => mb_strtolower(trim((string) ($color['name'] ?? ''))),
            $colors
        ), fn ($name) => $name !== ''));

        if (count($names) !== count(array_unique($names))) {
            throw ValidationException::withMessages(['colors' => 'Color names must be unique for this product.']);
        }

        return [
            'colors' => $colors,
            'variants' => $variants,
            'removed_variant_ids' => array_map('intval', $d['removed_variant_ids'] ?? []),
        ];
    }

    private function syncVariantConfiguration(Product $product, array $data): void
    {
        $colors = $data['colors'] ?? [];
        $rows = $data['variants'] ?? [];
        $colorMap = [];
        $keptColorIds = [];

        foreach ($colors as $index => $row) {
            $color = null;

            if (!empty($row['id'])) {
                $color = ProductColor::where('product_id', $product->id)->find($row['id']);
            }

            if (!$color) {
                $color = new ProductColor(['product_id' => $product->id]);
            }

            $color->name = trim($row['name']) ?: ($color->name ?: 'Color ' . ($index + 1));
            $color->hex = !empty($row['hex']) ? trim($row['hex']) : null;
            $color->sort_order = $index;
            $color->status = $row['status'] ?? 'active';

            $this->syncColorImages($color, $row);

            $color->save();
            $colorMap[(string) $row['key']] = $color->id;
            $keptColorIds[] = $color->id;
        }

        $existing = $product->variants()->get()->keyBy('id');
        $submittedIds = [];
        $combinations = [];

        foreach ($rows as $index => $row) {
            $size = trim((string) ($row['size'] ?? '')) ?: null;
            $colorKey = (string) ($row['color_key'] ?? '');
            $colorId = null;

            if ($colorKey !== '') {
                if (!array_key_exists($colorKey, $colorMap)) {
                    throw ValidationException::withMessages(["variants.$index.color_key" => 'Choose a valid color for this variant.']);
                }

                $colorId = $colorMap[$colorKey];
            }

            $combination = ($colorId ?? 'none') . '|' . ($size ?? 'none');

            if (isset($combinations[$combination])) {
                throw ValidationException::withMessages(["variants.$index.size" => 'This color and size combination is duplicated.']);
            }

            $combinations[$combination] = true;

            $variant = !empty($row['id']) ? $existing->get((int) $row['id']) : null;

            if ($variant) {
                $submittedIds[] = $variant->id;
            } else {
                $variant = new ProductVariant(['product_id' => $product->id]);
            }

            $sku = trim((string) ($row['sku'] ?? ''));
            $sku = $sku !== '' ? $sku : $this->autoSku($product, $colorId, $size, $variant->exists ? $variant->id : null);

            if (ProductVariant::where('sku', $sku)->when($variant->exists, fn ($query) => $query->where('id', '!=', $variant->id))->exists()) {
                throw ValidationException::withMessages(["variants.$index.sku" => 'This SKU is already used.']);
            }

            $barcode = trim((string) ($row['barcode'] ?? '')) ?: null;

            if ($barcode && ProductVariant::where('barcode', $barcode)->when($variant->exists, fn ($query) => $query->where('id', '!=', $variant->id))->exists()) {
                throw ValidationException::withMessages(["variants.$index.barcode" => 'This barcode is already used.']);
            }

            $variant->color_id = $colorId;
            $variant->size = $size;
            $variant->sku = $sku;
            $variant->barcode = $barcode;
            $variant->status = $row['status'] ?? 'active';

            if (!$variant->exists) {
                $variant->stock = 0;
            }

            $variant->save();

            $target = max(0, (int) $row['stock']);
            $delta = $target - (int) $variant->stock;

            if ($delta !== 0) {
                app(StockService::class)->applyVariant($variant, $delta, $variant->wasRecentlyCreated ? 'initial_stock' : 'manual_adjustment', [
                    'note' => $variant->wasRecentlyCreated ? 'Product created' : 'Admin product edit',
                    'admin_id' => session('admin_id'),
                ]);
            }
        }

        $explicitRemovals = $data['removed_variant_ids'] ?? [];

        foreach ($existing as $id => $variant) {
            if (in_array($id, $submittedIds, true)) {
                continue;
            }

            if (DB::table('order_items')->where('variant_id', $id)->exists()) {
                $variant->update(['status' => 'disabled']);

                continue;
            }

            if (!in_array($id, $explicitRemovals, true) && (int) $variant->stock > 0) {
                continue;
            }

            $variant->delete();
        }

        $removedColors = ProductColor::where('product_id', $product->id)
            ->whereNotIn('id', $keptColorIds)
            ->get();

        foreach ($removedColors as $color) {
            if (ProductVariant::where('color_id', $color->id)->exists()) {
                $color->update(['status' => 'disabled']);

                continue;
            }

            foreach ($color->image_list as $path) {
                Storage::disk('public')->delete($path);
            }

            $color->delete();
        }
    }

    private function syncColorImages(ProductColor $color, array $row): void
    {
        $old = $color->image_list;
        $submitted = array_key_exists('images', $row) && is_array($row['images'])
            ? array_values(array_filter($row['images'], fn ($path) => is_string($path) && trim($path) !== ''))
            : $old;
        $kept = array_values(array_intersect($old, $submitted));

        foreach (array_diff($old, $kept) as $path) {
            if ($path) {
                Storage::disk('public')->delete($path);
            }
        }

        $added = [];

        foreach (($row['new_images'] ?? []) as $file) {
            if ($file instanceof \Illuminate\Http\UploadedFile) {
                $added[] = $file->store('products/colors', 'public');
            }
        }

        if (($row['image'] ?? null) instanceof \Illuminate\Http\UploadedFile) {
            $added[] = $row['image']->store('products/colors', 'public');
        }

        $all = array_values(array_unique(array_merge($kept, $added)));
        $color->images = $all;
        $color->image = $all[0] ?? null;
    }

    private function autoSku(Product $product, ?int $colorId, ?string $size, ?int $ignoreId): string
    {
        $color = $colorId ? ProductColor::find($colorId) : null;
        $base = $product->sku ?: Str::upper(Str::slug($product->name, '-'));
        $suffix = array_values(array_filter([
            $color?->name ? Str::upper(Str::slug($color->name, '-')) : null,
            $size ? Str::upper(Str::slug($size, '-')) : null,
        ]));

        $sku = trim(implode('-', array_filter([$base, ...$suffix])), '-') ?: 'SKU';
        $candidate = $sku;
        $counter = 2;

        while (ProductVariant::where('sku', $candidate)->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))->exists()) {
            $candidate = $sku . '-' . $counter++;
        }

        return $candidate;
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'product';
        $slug = $base;
        $counter = 2;

        while (
            Product::withTrashed()
                ->where('slug', $slug)
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base . '-' . $counter++;
        }

        return $slug;
    }
}
