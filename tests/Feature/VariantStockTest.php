<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductColor;
use App\Models\ProductVariant;
use App\Services\Checkout\CheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use RuntimeException;
use Tests\TestCase;

class VariantStockTest extends TestCase
{
    use RefreshDatabase;

    private function variantProduct(): array
    {
        $product = Product::create([
            'name' => 'Variant Tee',
            'cat' => 'T-Shirt',
            'price' => 600,
            'old_price' => 0,
            'stock' => 0,
            'sizes' => 'M',
            'has_variants' => true,
            'status' => 'active',
            'featured' => false,
        ]);

        $black = ProductColor::create([
            'product_id' => $product->id,
            'name' => 'Black',
            'hex' => '#000000',
            'sort_order' => 0,
            'status' => 'active',
        ]);

        $white = ProductColor::create([
            'product_id' => $product->id,
            'name' => 'White',
            'hex' => '#ffffff',
            'sort_order' => 1,
            'status' => 'active',
        ]);

        $blackM = ProductVariant::create([
            'product_id' => $product->id,
            'color_id' => $black->id,
            'size' => 'M',
            'sku' => 'VT-BLACK-M',
            'stock' => 10,
            'status' => 'active',
        ]);

        $whiteM = ProductVariant::create([
            'product_id' => $product->id,
            'color_id' => $white->id,
            'size' => 'M',
            'sku' => 'VT-WHITE-M',
            'stock' => 12,
            'status' => 'active',
        ]);

        $product->forceFill(['stock' => 22])->saveQuietly();

        return [$product, $black, $white, $blackM, $whiteM];
    }

    private function admin(): self
    {
        $admin = Admin::create(['username' => 'admin', 'password' => 'admin']);

        return $this->withSession(['admin_id' => $admin->id]);
    }

    public function test_checkout_deducts_only_the_selected_variant(): void
    {
        [$product, , , $blackM, $whiteM] = $this->variantProduct();

        $this->withSession([
            'cart' => [
                $product->id . '|' . $blackM->id => [
                    'id' => $product->id,
                    'variant_id' => $blackM->id,
                    'size' => 'M',
                    'qty' => 2,
                ],
            ],
            'checkout_token' => 'variant-token',
        ])->post('/checkout', [
            'name' => 'Rahim Uddin',
            'phone' => '01711111111',
            'address' => 'House 1, Dhaka',
            'checkout_token' => 'variant-token',
        ])->assertRedirect();

        $order = Order::firstOrFail();

        $this->assertSame(8, $blackM->fresh()->stock);
        $this->assertSame(12, $whiteM->fresh()->stock);
        $this->assertSame(20, $product->fresh()->stock);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'variant_id' => $blackM->id,
            'color' => 'Black',
            'size' => 'M',
            'qty' => 2,
        ]);
        $this->assertDatabaseHas('inventory_movements', [
            'variant_id' => $blackM->id,
            'type' => 'order_sale',
            'quantity_delta' => -2,
            'color' => 'Black',
        ]);
    }

    public function test_cancelling_an_order_restores_only_the_purchased_variant(): void
    {
        [$product, , , $blackM, $whiteM] = $this->variantProduct();

        $this->withSession([
            'cart' => [
                $product->id . '|' . $blackM->id => [
                    'id' => $product->id,
                    'variant_id' => $blackM->id,
                    'size' => 'M',
                    'qty' => 2,
                ],
            ],
            'checkout_token' => 'cancel-token',
        ])->post('/checkout', [
            'name' => 'Rahim Uddin',
            'phone' => '01711111111',
            'address' => 'House 1, Dhaka',
            'checkout_token' => 'cancel-token',
        ]);

        $order = Order::firstOrFail();
        $this->admin()->patch("/admin/orders/{$order->id}", ['status' => 'Cancelled'])->assertRedirect();

        $this->assertSame(10, $blackM->fresh()->stock);
        $this->assertSame(12, $whiteM->fresh()->stock);
        $this->assertSame(22, $product->fresh()->stock);
    }

    public function test_checkout_rejects_overselling_one_variant(): void
    {
        [$product, , , $blackM, $whiteM] = $this->variantProduct();
        $blackM->update(['stock' => 1]);
        $product->forceFill(['stock' => 13])->saveQuietly();

        $this->withSession([
            'cart' => [
                $product->id . '|' . $blackM->id => [
                    'id' => $product->id,
                    'variant_id' => $blackM->id,
                    'size' => 'M',
                    'qty' => 2,
                ],
            ],
        ]);

        try {
            app(CheckoutService::class)->placeOrder([
                'name' => 'Rahim',
                'phone' => '01711111111',
                'address' => 'Dhaka',
                'delivery_area' => null,
                'note' => null,
            ], 'oversell-key');

            $this->fail('Expected an oversell exception.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('only 1 left', $e->getMessage());
        }

        $this->assertSame(1, $blackM->fresh()->stock);
        $this->assertSame(12, $whiteM->fresh()->stock);
        $this->assertSame(0, Order::count());
    }

    public function test_cart_keeps_different_variants_as_separate_lines(): void
    {
        [$product, , , $blackM, $whiteM] = $this->variantProduct();

        $this->post("/cart/add/{$product->id}", ['variant_id' => $blackM->id, 'qty' => 2, 'size' => 'M']);
        $this->post("/cart/add/{$product->id}", ['variant_id' => $whiteM->id, 'qty' => 1, 'size' => 'M']);

        $cart = session('cart');

        $this->assertCount(2, $cart);
        $this->assertSame(2, $cart[$product->id . '|' . $blackM->id]['qty']);
        $this->assertSame(1, $cart[$product->id . '|' . $whiteM->id]['qty']);
    }

    public function test_product_detail_exposes_colors_and_variants(): void
    {
        [$product, $black] = $this->variantProduct();

        $this->get(route('product', $product))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Store/Product')
                ->where('product.has_variants', true)
                ->has('product.colors', 2)
                ->has('product.variants', 2)
                ->where('product.colors.0.name', $black->name));
    }

    public function test_admin_can_create_a_product_with_colors_and_variants(): void
    {
        $this->admin()->post('/admin/products', [
            'name' => 'Admin Variant Tee',
            'sku' => 'AVT',
            'price' => 900,
            'old_price' => 0,
            'stock' => 0,
            'sizes' => 'M,L',
            'has_variants' => 1,
            'status' => 'active',
            'featured' => 0,
            'colors' => [
                ['key' => 'c1', 'name' => 'Black', 'hex' => '#000000'],
                ['key' => 'c2', 'name' => 'Navy Blue', 'hex' => '#1e3a8a'],
            ],
            'variants' => [
                ['color_key' => 'c1', 'size' => 'M', 'stock' => 10, 'sku' => '', 'barcode' => '', 'status' => 'active'],
                ['color_key' => 'c1', 'size' => 'L', 'stock' => 5, 'sku' => '', 'barcode' => '', 'status' => 'active'],
                ['color_key' => 'c2', 'size' => 'M', 'stock' => 8, 'sku' => '', 'barcode' => '', 'status' => 'active'],
            ],
        ])->assertRedirect('/admin/products');

        $product = Product::where('sizes', 'M,L')->latest('id')->firstOrFail();

        $this->assertTrue($product->has_variants);
        $this->assertSame(23, $product->stock);
        $this->assertSame(2, $product->colors()->count());
        $this->assertSame(3, $product->variants()->count());
        $this->assertSame(10, (int) $product->variants()->where('size', 'M')->whereHas('color', fn ($q) => $q->where('name', 'Black'))->value('stock'));
        $this->assertNotNull($product->variants()->where('size', 'L')->value('sku'));
    }

    public function test_admin_can_upload_a_color_specific_image(): void
    {
        Storage::fake('public');
        $image = UploadedFile::fake()->image('black.jpg');

        $this->admin()->post('/admin/products', [
            'name' => 'Image Variant Tee',
            'price' => 500,
            'old_price' => 0,
            'stock' => 0,
            'sizes' => 'M',
            'has_variants' => 1,
            'status' => 'active',
            'featured' => 0,
            'colors' => [
                ['key' => 'c1', 'name' => 'Black', 'hex' => '#000000', 'image' => $image],
            ],
            'variants' => [
                ['color_key' => 'c1', 'size' => 'M', 'stock' => 3, 'sku' => '', 'barcode' => '', 'status' => 'active'],
            ],
        ])->assertRedirect('/admin/products');

        $product = Product::where('name', 'Image Variant Tee')->firstOrFail();
        $color = $product->colors()->firstOrFail();

        $this->assertNotNull($color->image);
        Storage::disk('public')->assertExists($color->image);
    }

    public function test_backfill_migration_preserves_size_stock_without_inventory_movements(): void
    {
        $product = Product::create([
            'name' => 'Legacy Size Tee',
            'price' => 500,
            'old_price' => 0,
            'stock' => 0,
            'sizes' => 'M,L',
            'size_stock' => ['M' => 4, 'L' => 9],
            'status' => 'active',
            'featured' => false,
        ]);

        $this->assertSame(13, $product->fresh()->stock);

        $migration = require database_path('migrations/2026_11_19_000004_backfill_variants_from_size_stock.php');
        $migration->up();

        $product->refresh();

        $this->assertTrue($product->has_variants);
        $this->assertSame(13, $product->stock);
        $this->assertSame(2, $product->variants()->count());
        $this->assertSame(4, (int) $product->variants()->where('size', 'M')->value('stock'));
        $this->assertSame(9, (int) $product->variants()->where('size', 'L')->value('stock'));
        $this->assertSame(0, $product->variants()->whereNotNull('color_id')->count());
        $this->assertSame(0, InventoryMovement::count());

        $migration->up();

        $this->assertSame(2, $product->variants()->count());
    }

    public function test_admin_can_edit_one_variant_without_touching_siblings(): void
    {
        [$product, , , $blackM, $whiteM] = $this->variantProduct();
        [$black, $white] = $product->colors()->get()->all();

        $this->admin()->put("/admin/products/{$product->id}", [
            'name' => $product->name,
            'sku' => '',
            'price' => 600,
            'old_price' => 0,
            'stock' => 22,
            'sizes' => 'M',
            'has_variants' => 1,
            'status' => 'active',
            'featured' => 0,
            'colors' => [
                ['key' => (string) $black->id, 'id' => $black->id, 'name' => 'Black', 'hex' => '#000000'],
                ['key' => (string) $white->id, 'id' => $white->id, 'name' => 'White', 'hex' => '#ffffff'],
            ],
            'variants' => [
                ['id' => $blackM->id, 'color_key' => (string) $black->id, 'size' => 'M', 'stock' => 7, 'sku' => 'VT-BLACK-M', 'barcode' => '', 'status' => 'active'],
                ['id' => $whiteM->id, 'color_key' => (string) $white->id, 'size' => 'M', 'stock' => 12, 'sku' => 'VT-WHITE-M', 'barcode' => '', 'status' => 'active'],
            ],
        ])->assertRedirect('/admin/products');

        $this->assertSame(7, $blackM->fresh()->stock);
        $this->assertSame(12, $whiteM->fresh()->stock);
        $this->assertSame(19, $product->fresh()->stock);
        $this->assertDatabaseHas('inventory_movements', [
            'variant_id' => $blackM->id,
            'type' => 'manual_adjustment',
            'quantity_delta' => -3,
        ]);
    }
}
