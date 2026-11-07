<?php
namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    public function home()
    {
        return view('home', ['products' => Product::latest()->take(8)->get(), 'reviews' => Review::latest()->take(3)->get()]);
    }

    public function shop(Request $r)
    {
        $q = Product::query();
        if ($r->filled('cat')) $q->where('cat', $r->cat);
        if ($r->filled('q')) $q->where('name', 'like', '%' . $r->q . '%');
        if ($r->filled('min')) $q->where('price', '>=', (int) $r->min);
        if ($r->filled('max')) $q->where('price', '<=', (int) $r->max);
        if ($r->boolean('stock')) $q->where('stock', '>', 0);
        if ($r->sort === 'low') $q->orderBy('price');
        elseif ($r->sort === 'high') $q->orderByDesc('price');
        else $q->latest();
        return view('shop', ['products' => $q->paginate(24)->withQueryString()]);
    }

    public function product(Product $product)
    {
        return view('product', ['p' => $product]);
    }

    public function reviews()
    {
        $all = Review::latest()->get();
        return view('reviews', ['reviews' => $all, 'avg' => $all->count() ? round($all->avg('stars'), 2) : 0]);
    }

    public function storeReview(Request $r)
    {
        $d = $r->validate(['name' => 'nullable|max:60', 'text' => 'required|max:500', 'stars' => 'required|integer|between:1,5']);
        $d['name'] = $d['name'] ?: 'Customer';
        Review::create($d);
        return back()->with('ok', __('Thank you! Your review is live.'));
    }

    public function contact() { return view('contact'); }

    public function lang(string $l)
    {
        if (in_array($l, ['en', 'bn'])) session(['lang' => $l]);
        return back();
    }
}
