<?php
namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class CartController extends Controller
{
    private function lines(): array
    {
        $cart = session('cart', []);
        $products = Product::active()->whereIn('id', collect($cart)->pluck('id'))->get()->keyBy('id');
        $lines = [];
        foreach ($cart as $k => $c) {
            if ($p = $products->get($c['id'])) $lines[] = ['key' => $k, 'p' => $p, 'size' => $c['size'], 'qty' => $c['qty']];
        }
        return $lines;
    }

    public function index()
    {
        $lines = $this->lines();
        $sub = array_sum(array_map(fn($l) => $l['p']->price * $l['qty'], $lines));
        $ship = $lines ? (int) Setting::all_()['ship'] : 0;
        return view('cart', ['lines' => $lines, 'sub' => $sub, 'ship' => $ship, 'total' => $sub + $ship]);
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
        return $r->input('go') === 'buy' ? redirect()->route('cart') : back()->with('ok', __('Added to bag'));
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

    public function checkout(Request $r)
    {
        $d = $r->validate([
            'name' => 'required|max:80',
            'phone' => ['required', 'max:20', fn($a, $v, $f) => strlen(preg_replace('/\D/', '', $v)) < 10 ? $f(__('Please enter a valid phone number')) : null],
            'address' => 'required|max:300',
        ]);
        $lines = $this->lines();
        if (!$lines) return back()->withErrors(__('Your bag is empty.'));
        $ship = (int) Setting::all_()['ship'];
        try {
            $order = DB::transaction(function () use ($lines, $d, $ship) {
                $items = []; $sub = 0;
                foreach ($lines as $l) {
                    $p = Product::lockForUpdate()->find($l['p']->id);
                    if (!$p || $p->status !== 'active' || $p->stock < $l['qty']) throw new \RuntimeException(($p->name ?? 'Product') . ': ' . __('Out of stock'));
                    $p->decrement('stock', $l['qty']);
                    $items[] = ['product_id' => $p->id, 'name' => $p->name, 'size' => $l['size'], 'qty' => $l['qty'], 'price' => $p->price];
                    $sub += $p->price * $l['qty'];
                }
                $o = Order::create($d + ['subtotal' => $sub, 'ship' => $ship, 'total' => $sub + $ship, 'status' => 'Pending']);
                $o->items()->createMany($items);
                return $o->load('items');
            });
        } catch (\RuntimeException $e) {
            return back()->withErrors($e->getMessage());
        }
        session()->forget('cart');
        $this->mail($order);
        return redirect()->route('cart')->with('done', true);
    }

    private function mail(Order $o): void
    {
        $to = config('shop.order_email') ?: config('mail.from.address');
        if (!$to) return;
        try {
            $rows = '';
            foreach ($o->items as $i) $rows .= '<tr><td>' . e($i->name) . ($i->size ? ' (' . e($i->size) . ')' : '') . '</td><td>x' . $i->qty . '</td><td>৳' . ($i->price * $i->qty) . '</td></tr>';
            $html = '<h2>New order #' . $o->id . '</h2><p><b>' . e($o->name) . '</b><br>Phone: ' . e($o->phone) . '<br>Address: ' . e($o->address) . '</p><table cellpadding="6">' . $rows . '</table><p>Delivery: ৳' . $o->ship . '<br><b>Total: ৳' . $o->total . '</b> (Cash on Delivery)</p>';
            Mail::html($html, fn($m) => $m->to($to)->subject('New order ৳' . $o->total . ' from ' . $o->name));
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
