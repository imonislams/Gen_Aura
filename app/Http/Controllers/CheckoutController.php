<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\Checkout\CheckoutService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Inertia\Inertia;
use RuntimeException;

class CheckoutController extends Controller
{
    public function show(CheckoutService $checkout)
    {
        $lines = $checkout->lines();
        $totals = $checkout->totals($lines);

        $token = session('checkout_token') ?: (string) Str::uuid();
        session(['checkout_token' => $token]);

        return Inertia::render('Store/Checkout', [
            'lines' => array_map(fn ($line) => [
                'key' => $line['key'],
                'name' => $line['p']->name,
                'size' => $line['size'],
                'qty' => $line['qty'],
                'price' => $line['p']->price,
                'line_total' => $line['p']->price * $line['qty'],
                'image_url' => $line['p']->image_url,
            ], $lines),
            'subtotal' => $totals['subtotal'],
            'ship' => $totals['ship'],
            'total' => $totals['total'],
            'checkoutToken' => $token,
        ]);
    }

    public function store(Request $request, CheckoutService $checkout)
    {
        $data = $request->validate([
            'name' => 'required|max:80',
            'phone' => ['required', 'max:20', fn ($attribute, $value, $fail) => strlen(preg_replace('/\D/', '', $value)) < 10 ? $fail(__('Please enter a valid phone number')) : null],
            'address' => 'required|max:300',
            'delivery_area' => 'nullable|max:80',
            'note' => 'nullable|max:300',
            'checkout_token' => 'required|string',
        ]);

        if (!hash_equals((string) session('checkout_token'), (string) $data['checkout_token'])) {
            return back()->withErrors(__('This checkout session expired. Please review your bag and try again.'));
        }

        try {
            $order = $checkout->placeOrder([
                'name' => $data['name'],
                'phone' => $data['phone'],
                'address' => $data['address'],
                'delivery_area' => $data['delivery_area'] ?? null,
                'note' => $data['note'] ?? null,
            ], $data['checkout_token']);
        } catch (RuntimeException $e) {
            return back()->withErrors($e->getMessage());
        }

        session()->forget(['cart', 'checkout_token']);

        return redirect(URL::temporarySignedRoute('order.success', now()->addDays(7), ['order' => $order->id]))
            ->with('done', true);
    }

    public function success(Order $order)
    {
        $order->load('items');

        return Inertia::render('Store/OrderSuccess', [
            'order' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'name' => $order->name,
                'phone' => $order->phone,
                'address' => $order->address,
                'delivery_area' => $order->delivery_area,
                'note' => $order->note,
                'subtotal' => $order->subtotal,
                'ship' => $order->ship,
                'total' => $order->total,
                'status' => $order->status,
                'payment_method' => $order->payment_method,
                'created_at' => $order->created_at?->toIso8601String(),
                'items' => $order->items->map(fn ($item) => [
                    'id' => $item->id,
                    'name' => $item->name,
                    'size' => $item->size,
                    'qty' => $item->qty,
                    'price' => $item->price,
                    'line_total' => $item->price * $item->qty,
                ])->values(),
            ],
        ]);
    }
}
