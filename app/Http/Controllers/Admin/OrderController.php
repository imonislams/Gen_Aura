<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function index(Request $r)
    {
        $q = Order::with('items')->latest();
        if ($r->filled('status')) $q->where('status', $r->status);
        if ($r->filled('q')) $q->where(fn($x) => $x->where('name', 'like', '%' . $r->q . '%')->orWhere('phone', 'like', '%' . $r->q . '%'));
        return view('admin.orders', ['orders' => $q->paginate(30)->withQueryString()]);
    }

    public function update(Request $r, Order $order)
    {
        $st = $r->validate(['status' => ['required', Rule::in(Order::STATUS)]])['status'];
        $was = $order->status === 'Cancelled'; $now = $st === 'Cancelled';
        if ($was !== $now) {
            foreach ($order->items as $i) {
                $p = Product::find($i->product_id);
                if (!$p) continue;
                $now ? $p->increment('stock', $i->qty) : $p->update(['stock' => max(0, $p->stock - $i->qty)]);
            }
        }
        $order->update(['status' => $st]);
        return back()->with('ok', 'Order updated');
    }

    public function destroy(Order $order)
    {
        $order->delete();
        return back()->with('ok', 'Order deleted');
    }
}
