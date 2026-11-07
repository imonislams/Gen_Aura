<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class OrderController extends Controller
{
    public function index(Request $r)
    {
        $q = Order::with('items')->latest();

        if ($r->filled('status')) {
            $q->where('status', $r->status);
        }

        if ($r->filled('q')) {
            $q->where(fn ($x) => $x->where('name', 'like', '%' . $r->q . '%')
                ->orWhere('phone', 'like', '%' . $r->q . '%')
                ->orWhere('order_number', 'like', '%' . $r->q . '%'));
        }

        return Inertia::render('Admin/Orders/Index', [
            'orders' => $q->with('items')->paginate(30)->withQueryString()->through(fn (Order $order) => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'name' => $order->name,
                'phone' => $order->phone,
                'address' => $order->address,
                'delivery_area' => $order->delivery_area,
                'total' => $order->total,
                'status' => $order->status,
                'payment_method' => $order->payment_method,
                'created_at' => $order->created_at?->toIso8601String(),
                'items_count' => $order->items->count(),
                'items_summary' => $order->items->map(fn ($item) => $item->name . ($item->size ? ' (' . $item->size . ')' : '') . ' ×' . $item->qty)->implode(', '),
            ]),
            'filters' => $r->only(['status', 'q']),
            'statuses' => Order::STATUS,
        ]);
    }

    public function show(Order $order)
    {
        $order->load(['items', 'customer', 'statusHistory']);

        return Inertia::render('Admin/Orders/Show', [
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
                'updated_at' => $order->updated_at?->toIso8601String(),
                'customer' => $order->customer ? [
                    'id' => $order->customer->id,
                    'name' => $order->customer->name,
                    'phone' => $order->customer->phone,
                ] : null,
                'items' => $order->items->map(fn ($item) => [
                    'id' => $item->id,
                    'name' => $item->name,
                    'size' => $item->size,
                    'qty' => $item->qty,
                    'price' => $item->price,
                    'line_total' => $item->price * $item->qty,
                ])->values(),
                'history' => $order->statusHistory->map(fn ($history) => [
                    'id' => $history->id,
                    'status' => $history->status,
                    'note' => $history->note,
                    'created_at' => $history->created_at?->toIso8601String(),
                ])->values(),
                'invoice_url' => route('admin.orders.invoice', $order),
            ],
            'statuses' => Order::STATUS,
        ]);
    }

    public function invoice(Order $order)
    {
        $order->load('items');
        $settings = Setting::all_();

        $pdf = Pdf::loadView('exports.invoice', compact('order', 'settings'));

        return $pdf->download('invoice-' . ($order->order_number ?: $order->id) . '.pdf');
    }

    public function update(Request $r, Order $order)
    {
        $st = $r->validate(['status' => ['required', Rule::in(Order::STATUS)]])['status'];
        $was = $order->status === 'Cancelled';
        $now = $st === 'Cancelled';

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
        return back()->withErrors('Orders are not deleted. Cancel the order instead to preserve order history.');
    }
}
