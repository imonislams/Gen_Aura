<?php
namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Review;
use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ShopController extends Controller
{
    public function home()
    {
        $products = Product::active()->latest()->take(8)->get();
        $reviews = Review::approved()->latest()->take(3)->get();
        $settings = Setting::all_();
        $heroProduct = $products->first();
        $heroImage = !empty($settings['hero_image'])
            ? asset('storage/' . $settings['hero_image'])
            : $heroProduct?->image_url;

        return Inertia::render('Store/Home', [
            'products' => $products->map(fn (Product $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'cat' => $p->cat,
                'description' => $p->description,
                'price' => $p->price,
                'old_price' => $p->old_price,
                'stock' => $p->stock,
                'off' => $p->off,
                'image_url' => $p->image_url,
                'url' => route('product', $p),
            ])->values(),
            'reviews' => $reviews->map(fn (Review $r) => [
                'id' => $r->id,
                'name' => $r->name,
                'text' => $r->text,
                'stars' => $r->stars,
            ])->values(),
            'heroImage' => $heroImage,
            'heroProduct' => $heroProduct ? [
                'name' => $heroProduct->name,
                'price' => $heroProduct->price,
            ] : null,
        ]);
    }

    public function shop(Request $r)
    {
        $q = Product::active();
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
        abort_if($product->status !== 'active', 404);

        return view('product', ['p' => $product]);
    }

    public function reviews()
    {
        $all = Review::approved()->latest()->get();
        return view('reviews', ['reviews' => $all, 'avg' => $all->count() ? round($all->avg('stars'), 2) : 0]);
    }

    public function storeReview(Request $r)
    {
        $d = $r->validate(['name' => 'nullable|max:60', 'text' => 'required|max:500', 'stars' => 'required|integer|between:1,5']);
        $d['name'] = $d['name'] ?: 'Customer';
        $d['status'] = 'pending';
        Review::create($d);
        return back()->with('ok', __('Thank you! Your review will appear after approval.'));
    }

    public function contact() { return Inertia::render('Store/Contact'); }

    public function lang(string $l)
    {
        if (in_array($l, ['en', 'bn'])) session(['lang' => $l]);
        return back();
    }
}
