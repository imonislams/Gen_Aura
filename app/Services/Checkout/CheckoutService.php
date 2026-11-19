<?php

namespace App\Services\Checkout;

use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\Inventory\StockService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

class CheckoutService
{
    public function __construct(private StockService $stock)
    {
    }

    public function lines(): array
    {
        $cart = session('cart', []);
        $products = Product::active()->with(['variants.color'])
            ->whereIn('id', collect($cart)->pluck('id'))
            ->get()
            ->keyBy('id');

        $lines = [];
        foreach ($cart as $key => $item) {
            if ($product = $products->get($item['id'])) {
                $variant = $product->has_variants ? $this->resolveVariant($product, $item) : null;

                $lines[] = [
                    'key' => $key,
                    'p' => $product,
                    'variant' => $variant,
                    'size' => $variant?->size ?? (string) ($item['size'] ?? ''),
                    'color' => $variant?->color?->name,
                    'qty' => (int) $item['qty'],
                ];
            }
        }

        return $lines;
    }

    public function totals(array $lines, ?int $ship = null): array
    {
        $subtotal = array_sum(array_map(fn ($line) => $line['p']->price * $line['qty'], $lines));
        $ship = $lines ? ($ship ?? 0) : 0;

        return ['subtotal' => $subtotal, 'ship' => $ship, 'total' => $subtotal + $ship];
    }

    public function placeOrder(array $customer, string $idempotencyKey): Order
    {
        $existing = Order::where('idempotency_key', $idempotencyKey)->first();
        if ($existing) {
            return $existing->load('items');
        }

        $lines = $this->lines();
        if (!$lines) {
            throw new RuntimeException('Your bag is empty.');
        }

        $zone = null;
        if (!empty($customer['delivery_zone_id'])) {
            $zone = DeliveryZone::active()->find($customer['delivery_zone_id']);
            if (!$zone) {
                throw new RuntimeException('Please select a valid delivery zone.');
            }
            $customer['delivery_area'] = $zone->name;
        }
        unset($customer['delivery_zone_id']);

        $totals = $this->totals($lines, $zone?->charge);

        try {
            $order = DB::transaction(function () use ($lines, $customer, $totals, $idempotencyKey) {
                $items = [];
                $subtotal = 0;
                $stockLines = [];

                foreach ($lines as $line) {
                    $product = Product::lockForUpdate()->find($line['p']->id);
                    $variant = null;
                    $size = (string) ($line['size'] ?? '');

                    if (!$product || $product->status !== 'active') {
                        throw new RuntimeException(($product->name ?? 'Product') . ': Out of stock');
                    }

                    if ($product->has_variants) {
                        $variant = $this->lockVariant($product, $line);

                        if (!$variant || !$variant->isAvailable()) {
                            throw new RuntimeException($product->name . ($size !== '' ? ' (' . $size . ')' : '') . ': no longer available.');
                        }

                        $size = (string) ($variant->size ?? '');

                        if ((int) $variant->stock < $line['qty']) {
                            throw new RuntimeException($product->name . ' (' . $variant->label . '): only ' . (int) $variant->stock . ' left.');
                        }
                    } else {
                        if (!empty($product->size_list) && $size === '') {
                            throw new RuntimeException($product->name . ': choose a size.');
                        }

                        if (!empty($product->size_list) && !in_array($size, $product->size_list, true)) {
                            throw new RuntimeException($product->name . ': invalid size.');
                        }

                        if ($product->hasSizeStock()) {
                            $available = (int) ($product->size_stock[$size] ?? 0);

                            if ($available < $line['qty']) {
                                throw new RuntimeException($product->name . ' (' . $size . '): only ' . $available . ' left.');
                            }
                        } elseif ($product->stock < $line['qty']) {
                            throw new RuntimeException($product->name . ': Out of stock');
                        }
                    }

                    $items[] = [
                        'product_id' => $product->id,
                        'variant_id' => $variant?->id,
                        'name' => $product->name,
                        'size' => $variant?->size ?? $line['size'],
                        'color' => $variant?->color?->name,
                        'sku' => $variant?->sku,
                        'qty' => $line['qty'],
                        'price' => $product->price,
                    ];

                    $stockLines[] = ['product' => $product, 'variant' => $variant, 'qty' => $line['qty'], 'size' => $size];
                    $subtotal += $product->price * $line['qty'];
                }

                $order = Order::create($customer + [
                    'subtotal' => $subtotal,
                    'ship' => $totals['ship'],
                    'total' => $subtotal + $totals['ship'],
                    'status' => 'Pending',
                    'payment_method' => 'COD',
                    'idempotency_key' => $idempotencyKey,
                ]);

                $order->items()->createMany($items);

                foreach ($stockLines as $entry) {
                    $meta = [
                        'reference_type' => 'order',
                        'reference_id' => $order->id,
                        'size' => $entry['size'],
                        'note' => 'Order sale',
                    ];

                    if ($entry['variant']) {
                        $this->stock->applyVariant($entry['variant'], -$entry['qty'], 'order_sale', $meta);
                    } else {
                        $this->stock->apply($entry['product'], -$entry['qty'], 'order_sale', $meta);
                    }
                }

                return $order->load('items');
            });
        } catch (\Illuminate\Database\QueryException $e) {
            $existing = Order::where('idempotency_key', $idempotencyKey)->first();
            if ($existing) {
                return $existing->load('items');
            }

            throw $e;
        }

        $this->mail($order);

        return $order;
    }

    public function mail(Order $order): void
    {
        $to = config('shop.order_email');
        if (!$to) {
            return;
        }

        try {
            $rows = '';
            foreach ($order->items as $item) {
                $variant = trim(($item->color ? $item->color . ' / ' : '') . ($item->size ?? ''), ' /');
                $rows .= '<tr><td>' . e($item->name) . ($variant !== '' ? ' (' . e($variant) . ')' : '') . '</td><td>x' . $item->qty . '</td><td>৳' . ($item->price * $item->qty) . '</td></tr>';
            }

            $html = '<h2>New order #' . $order->order_number . '</h2>'
                . '<p><b>' . e($order->name) . '</b><br>Phone: ' . e($order->phone) . '<br>Address: ' . e($order->address) . '</p>'
                . '<table cellpadding="6">' . $rows . '</table>'
                . '<p>Delivery: ৳' . $order->ship . '<br><b>Total: ৳' . $order->total . '</b> (Cash on Delivery)</p>';

            Mail::html($html, fn ($message) => $message->to($to)->subject('New order ৳' . $order->total . ' from ' . $order->name));
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private function resolveVariant(Product $product, array $item): ?ProductVariant
    {
        $variants = $product->relationLoaded('variants') ? $product->variants : $product->variants()->with('color')->get();

        if (!empty($item['variant_id'])) {
            return $variants->firstWhere('id', (int) $item['variant_id']);
        }

        $size = (string) ($item['size'] ?? '');
        $matches = $variants->filter(fn (ProductVariant $variant) => (string) $variant->size === $size);

        if ($matches->count() === 1) {
            return $matches->first();
        }

        return $matches->firstWhere('color_id', null);
    }

    private function lockVariant(Product $product, array $line): ?ProductVariant
    {
        $query = ProductVariant::with('color')->where('product_id', $product->id)->lockForUpdate();

        if ($line['variant'] ?? null) {
            return (clone $query)->find($line['variant']->id);
        }

        return $query->where('size', (string) ($line['size'] ?? ''))->whereNull('color_id')->first();
    }
}
