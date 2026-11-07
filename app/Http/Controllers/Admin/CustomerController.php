<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CustomerController extends Controller
{
    public function index(Request $r)
    {
        $q = Customer::query();

        if ($r->filled('q')) {
            $q->where(fn ($x) => $x->where('name', 'like', '%' . $r->q . '%')->orWhere('phone', 'like', '%' . $r->q . '%'));
        }

        $customers = $q->orderByDesc('last_order_at')->paginate(30)->withQueryString();

        return Inertia::render('Admin/Customers/Index', [
            'customers' => $customers->through(fn (Customer $customer) => [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'total_orders' => $customer->total_orders,
                'total_purchase' => $customer->total_purchase,
                'last_order_at' => $customer->last_order_at?->toIso8601String(),
            ]),
            'filters' => $r->only('q'),
        ]);
    }

    public function show(Customer $customer)
    {
        $customer->load(['orders' => fn ($query) => $query->withCount('items')->latest()]);

        return Inertia::render('Admin/Customers/Show', [
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'total_orders' => $customer->total_orders,
                'total_purchase' => $customer->total_purchase,
                'last_order_at' => $customer->last_order_at?->toIso8601String(),
                'created_at' => $customer->created_at?->toIso8601String(),
                'orders' => $customer->orders->map(fn ($order) => [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'status' => $order->status,
                    'total' => $order->total,
                    'items_count' => $order->items_count,
                    'created_at' => $order->created_at?->toIso8601String(),
                ])->values(),
            ],
        ]);
    }
}
