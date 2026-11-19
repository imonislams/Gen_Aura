<?php

namespace App\Http\Controllers;

use App\Models\DeliveryZone;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\Analytics\AnalyticsTracker;
use App\Services\Checkout\CheckoutService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Inertia\Inertia;

class CartController extends Controller
{
    public function index(CheckoutService $checkout)
    {
        $lines = $checkout->lines();
        $defaultCharge = DeliveryZone::active()->ordered()->value('charge');
        $totals = $checkout->totals($lines, $defaultCharge !== null ? (int) $defaultCharge : null);

        return Inertia::render('Store/Cart', [
            'lines' => array_map(fn ($line) => [
                'key' => $line['key'],
                'product_id' => $line['p']->id,
                'variant_id' => $line['variant']?->id,
                'name' => $line['p']->name,
                'color' => $line['color'],
                'size' => $line['size'],
                'qty' => $line['qty'],
                'price' => $line['p']->price,
                'line_total' => $line['p']->price * $line['qty'],
                'image_url' => $line['variant']?->image_url ?? $line['p']->image_url,
                'available' => $this->availableForLine($line),
                'url' => route('product', $line['p']),
            ], $lines),
            'subtotal' => $totals['subtotal'],
            'ship' => $totals['ship'],
            'total' => $totals['total'],
        ]);
    }

    public function add(Request $r, Product $product)
    {
        if ($product->status !== 'active') {
            return back()->withErrors('This product is not available');
        }

        $product->load(['variants.color']);

        $variantId = $r->filled('variant_id') ? (int) $r->input('variant_id') : null;
        $colorId = $r->filled('color_id') ? (int) $r->input('color_id') : null;
        $size = trim((string) $r->input('size', ''));
        $variant = null;

        if ($product->has_variants) {
            $variant = $this->resolveVariant($product, $variantId, $size, $colorId);

            if (!$variant) {
                return back()->withErrors('Please choose a valid color and size.');
            }

            if ($variant->status !== 'active' || ($variant->color && $variant->color->status !== 'active')) {
                return back()->withErrors($this->variantLabel($product, $variant) . ' is not available.');
            }

            $size = (string) ($variant->size ?? '');
            $available = (int) $variant->stock;

            if ($available <= 0) {
                return back()->withErrors($this->variantLabel($product, $variant) . ' is sold out.');
            }

            $key = $product->id . '|' . $variant->id;
        } else {
            $sizes = $product->size_list;

            if (!empty($sizes)) {
                if ($size === '') {
                    return back()->withErrors('Please choose a size.');
                }

                if (!in_array($size, $sizes, true)) {
                    return back()->withErrors('Invalid size.');
                }
            }

            $available = $this->availableFor($product, $size);

            if ($available <= 0) {
                return back()->withErrors($size !== '' ? 'Size ' . $size . ' is sold out.' : 'Out of stock');
            }

            $key = $product->id . '|' . $size;
        }

        $qty = max(1, min(50, $available, (int) $r->input('qty', 1)));
        $cart = session('cart', []);
        $line = [
            'id' => $product->id,
            'size' => $size,
            'qty' => min($available, ($cart[$key]['qty'] ?? 0) + $qty),
        ];

        if ($variant) {
            $line['variant_id'] = $variant->id;
            $line['color'] = $variant->color?->name;
        }

        $cart[$key] = $line;
        session(['cart' => $cart]);

        app(AnalyticsTracker::class)->event($r, 'add_to_cart', [
            'product_id' => $product->id,
            'category' => $product->cat,
            'metadata' => [
                'size' => $size,
                'color' => $variant?->color?->name,
                'variant_id' => $variant?->id,
                'qty' => $cart[$key]['qty'],
                'price' => $product->price,
            ],
        ]);

        return $r->input('go') === 'buy' ? redirect()->route('checkout.show') : back()->with('ok', 'Added to bag');
    }

    public function update(Request $r)
    {
        $cart = session('cart', []);
        $key = (string) $r->input('key');

        if (isset($cart[$key])) {
            $oldQty = (int) $cart[$key]['qty'];
            $product = Product::with(['variants.color'])->find($cart[$key]['id']);
            $variant = null;
            $available = 50;

            if ($product && $product->has_variants) {
                $variant = $this->resolveVariant(
                    $product,
                    isset($cart[$key]['variant_id']) ? (int) $cart[$key]['variant_id'] : null,
                    (string) ($cart[$key]['size'] ?? '')
                );

                $available = $variant && $variant->isAvailable() ? (int) $variant->stock : 0;

                if ($variant) {
                    $cart[$key]['variant_id'] = $variant->id;
                    $cart[$key]['size'] = (string) ($variant->size ?? '');
                    $cart[$key]['color'] = $variant->color?->name;
                }
            } elseif ($product) {
                $available = $this->availableFor($product, (string) ($cart[$key]['size'] ?? ''));
            }

            $requested = max(0, (int) $r->input('qty'));
            $qty = min(50, $requested, max(0, $available));

            if ($qty === 0) {
                unset($cart[$key]);
            } else {
                $cart[$key]['qty'] = $qty;
            }

            session(['cart' => $cart]);

            app(AnalyticsTracker::class)->event($r, $qty === 0 ? 'remove_from_cart' : 'cart_qty_update', [
                'product_id' => $cart[$key]['id'] ?? null,
                'metadata' => [
                    'size' => $cart[$key]['size'] ?? null,
                    'color' => $cart[$key]['color'] ?? null,
                    'old_qty' => $oldQty,
                    'qty' => $qty,
                ],
            ]);
        }

        return back();
    }

    public function remove(Request $r)
    {
        $cart = session('cart', []);
        $key = (string) $r->input('key');
        $item = $cart[$key] ?? null;
        unset($cart[$key]);
        session(['cart' => $cart]);

        if ($item) {
            app(AnalyticsTracker::class)->event($r, 'remove_from_cart', [
                'product_id' => $item['id'],
                'metadata' => [
                    'size' => $item['size'],
                    'color' => $item['color'] ?? null,
                    'qty' => $item['qty'],
                ],
            ]);
        }

        return back();
    }

    public function checkout(Request $r, CheckoutService $checkout)
    {
        $d = $r->validate([
            'name' => 'required|max:80',
            'phone' => ['required', 'max:20', fn ($a, $v, $f) => strlen(preg_replace('/\D/', '', $v)) < 10 ? $f('Please enter a valid phone number') : null],
            'address' => 'required|max:300',
        ]);

        try {
            $order = $checkout->placeOrder($d, (string) Str::uuid());
        } catch (\RuntimeException $e) {
            return back()->withErrors($e->getMessage());
        }

        app(AnalyticsTracker::class)->orderCompleted($r, $order);
        session()->forget('cart');

        return redirect(URL::temporarySignedRoute('order.success', now()->addDays(7), ['order' => $order->id]))
            ->with('done', true);
    }

    private function availableFor(Product $product, string $size): int
    {
        if ($product->hasSizeStock()) {
            return (int) ($product->size_stock[$size] ?? 0);
        }

        return (int) $product->stock;
    }

    private function availableForLine(array $line): int
    {
        if ($line['variant']) {
            return $line['variant']->isAvailable() ? (int) $line['variant']->stock : 0;
        }

        if ($line['p']->has_variants) {
            return 0;
        }

        return $this->availableFor($line['p'], (string) $line['size']);
    }

    private function resolveVariant(Product $product, ?int $variantId, string $size, ?int $colorId = null): ?ProductVariant
    {
        $variants = $product->relationLoaded('variants') ? $product->variants : $product->variants()->with('color')->get();

        if ($variantId) {
            return $variants->firstWhere('id', $variantId);
        }

        if ($size === '') {
            return null;
        }

        $matches = $variants->filter(function (ProductVariant $variant) use ($size, $colorId) {
            if ((string) $variant->size !== $size) {
                return false;
            }

            return $colorId === null || (int) $variant->color_id === $colorId;
        });

        if ($matches->count() === 1) {
            return $matches->first();
        }

        return $colorId === null ? $matches->firstWhere('color_id', null) : null;
    }

    private function variantLabel(Product $product, ProductVariant $variant): string
    {
        $label = trim(($variant->color?->name ? $variant->color->name . ' / ' : '') . ($variant->size ?? ''), ' /');

        return $product->name . ($label !== '' ? ' (' . $label . ')' : '');
    }
}
