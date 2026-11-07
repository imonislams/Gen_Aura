<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\Checkout\CheckoutService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class CartController extends Controller
{
    public function index(CheckoutService $checkout)
    {
        $lines = $checkout->lines();
        $totals = $checkout->totals($lines);

        return view('cart', [
            'lines' => $lines,
            'sub' => $totals['subtotal'],
            'ship' => $totals['ship'],
            'total' => $totals['total'],
        ]);
    }

    public function add(Request $r, Product $product)
    {
        if ($product->status !== 'active') return back()->withErrors(__('This product is not available'));
        if ($product->stock <= 0) return back()->withErrors(__('Out of stock'));

        $size = (string) $r->input('size', '');
        $key = $product->id . '|' . $size;
        $cart = session('cart', []);
        $cart[$key] = ['id' => $product->id, 'size' => $size, 'qty' => min(50, ($cart[$key]['qty'] ?? 0) + 1)];
        session(['cart' => $cart]);

        return $r->input('go') === 'buy' ? redirect()->route('checkout.show') : back()->with('ok', __('Added to bag'));
    }

    public function update(Request $r)
    {
        $cart = session('cart', []);
        $key = (string) $r->input('key');

        if (isset($cart[$key])) {
            $qty = min(50, max(0, (int) $r->input('qty')));
            if ($qty === 0) unset($cart[$key]); else $cart[$key]['qty'] = $qty;
            session(['cart' => $cart]);
        }

        return back();
    }

    public function remove(Request $r)
    {
        $cart = session('cart', []);
        unset($cart[(string) $r->input('key')]);
        session(['cart' => $cart]);

        return back();
    }

    public function checkout(Request $r, CheckoutService $checkout)
    {
        $d = $r->validate([
            'name' => 'required|max:80',
            'phone' => ['required', 'max:20', fn ($a, $v, $f) => strlen(preg_replace('/\D/', '', $v)) < 10 ? $f(__('Please enter a valid phone number')) : null],
            'address' => 'required|max:300',
        ]);

        try {
            $order = $checkout->placeOrder($d, (string) Str::uuid());
        } catch (\RuntimeException $e) {
            return back()->withErrors($e->getMessage());
        }

        session()->forget('cart');

        return redirect(URL::temporarySignedRoute('order.success', now()->addDays(7), ['order' => $order->id]))
            ->with('done', true);
    }
}
