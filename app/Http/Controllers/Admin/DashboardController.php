<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $r)
    {
        $to = Carbon::parse($r->input('to', now()->toDateString()))->endOfDay();
        $from = Carbon::parse($r->input('from', now()->subDays(29)->toDateString()))->startOfDay();
        if ($from->diffInDays($to) > 92) $from = $to->copy()->subDays(92)->startOfDay();

        $orders = Order::with('items')->where('status', '!=', 'Cancelled')->where('created_at', '>=', now()->startOfMonth()->subMonths(11))->get();
        $daily = [];
        for ($d = $from->copy(); $d <= $to; $d->addDay()) $daily[$d->toDateString()] = ['l' => $d->format('j/n'), 'n' => 0, 'r' => 0, 'u' => 0];
        $monthly = [];
        for ($i = 11; $i >= 0; $i--) { $m = now()->startOfMonth()->subMonths($i); $monthly[$m->format('Y-m')] = ['l' => $m->format('M'), 'n' => 0, 'r' => 0, 'u' => 0]; }
        foreach ($orders as $o) {
            $u = $o->items->sum('qty'); $dk = $o->created_at->toDateString(); $mk = $o->created_at->format('Y-m');
            if (isset($daily[$dk])) { $daily[$dk]['n']++; $daily[$dk]['r'] += $o->total; $daily[$dk]['u'] += $u; }
            if (isset($monthly[$mk])) { $monthly[$mk]['n']++; $monthly[$mk]['r'] += $o->total; $monthly[$mk]['u'] += $u; }
        }
        $today = $orders->filter(fn($o) => $o->created_at->isToday());
        $top = OrderItem::selectRaw('name, SUM(qty) as q')
            ->whereHas('order', fn($q) => $q->where('status', '!=', 'Cancelled')->whereBetween('created_at', [$from, $to]))
            ->groupBy('name')->orderByDesc('q')->limit(5)->get();
        $low = Product::where('stock', '<=', 3)->orderBy('stock')->get();
        $sum = fn($k) => array_sum(array_column($daily, $k));
        $stats = [
            'Today orders' => $today->count(), 'Today revenue' => '৳' . number_format($today->sum('total')),
            'Orders (range)' => $sum('n'), 'Revenue (range)' => '৳' . number_format($sum('r')), 'Units sold (range)' => $sum('u'),
            'Pending' => Order::where('status', 'Pending')->count(), 'Low / out of stock' => $low->count(), 'Products' => Product::count(),
        ];
        return Inertia::render('Admin/Dashboard', [
            'daily' => array_values($daily),
            'monthly' => array_values($monthly),
            'top' => $top->map(fn ($item) => ['name' => $item->name, 'q' => (int) $item->q])->values(),
            'low' => $low->map(fn ($product) => ['id' => $product->id, 'name' => $product->name, 'stock' => $product->stock])->values(),
            'stats' => $stats,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
        ]);
    }
}
