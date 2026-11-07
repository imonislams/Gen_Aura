<?php
namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Review;
use App\Models\Setting;
use App\Support\Store\ProductPresenter;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ShopController extends Controller
{
    public function home()
    {
        $products = Product::active()->with('category')->latest()->take(8)->get();
        $reviews = Review::approved()->latest()->take(3)->get();
        $settings = Setting::all_();
        $heroProduct = $products->first();
        $heroImage = !empty($settings['hero_image'])
            ? asset('storage/' . $settings['hero_image'])
            : $heroProduct?->image_url;

        return Inertia::render('Store/Home', [
            'products' => $products->map(fn (Product $p) => ProductPresenter::card($p))->values(),
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
        $q = Product::active()->with('category');
        if ($r->filled('cat')) $q->where('cat', $r->cat);
        if ($r->filled('q')) $q->where('name', 'like', '%' . $r->q . '%');
        if ($r->filled('min')) $q->where('price', '>=', (int) $r->min);
        if ($r->filled('max')) $q->where('price', '<=', (int) $r->max);
        if ($r->boolean('stock')) $q->where('stock', '>', 0);
        if ($r->sort === 'low') $q->orderBy('price');
        elseif ($r->sort === 'high') $q->orderByDesc('price');
        else $q->latest();

        return Inertia::render('Store/Shop', [
            'products' => $q->paginate(24)->withQueryString()->through(fn (Product $p) => ProductPresenter::card($p)),
            'filters' => $r->only(['cat', 'q', 'min', 'max', 'sort', 'stock']),
            'categories' => Category::active()->orderBy('sort_order')->orderBy('name')->get(['id', 'name', 'slug']),
        ]);
    }

    public function product(Product $product)
    {
        abort_if($product->status !== 'active', 404);

        $product->load('category');

        $related = Product::active()->with('category')
            ->where('id', '!=', $product->id)
            ->when($product->category_id, fn ($query) => $query->where('category_id', $product->category_id))
            ->latest()
            ->take(4)
            ->get();

        if ($related->isEmpty()) {
            $related = Product::active()->with('category')
                ->where('id', '!=', $product->id)
                ->latest()
                ->take(4)
                ->get();
        }

        $reviews = Review::approved()->where('product_id', $product->id)->latest()->limit(12)->get();

        return Inertia::render('Store/Product', [
            'product' => ProductPresenter::detail($product),
            'related' => $related->map(fn (Product $p) => ProductPresenter::card($p))->values(),
            'reviews' => $reviews->map(fn (Review $review) => [
                'id' => $review->id,
                'name' => $review->name,
                'text' => $review->text,
                'stars' => $review->stars,
                'created_at' => $review->created_at?->toIso8601String(),
            ])->values(),
        ]);
    }

    public function reviews()
    {
        $all = Review::approved()->with('product')->latest()->get();

        return Inertia::render('Store/Reviews', [
            'reviews' => $all->map(fn (Review $review) => [
                'id' => $review->id,
                'name' => $review->name,
                'text' => $review->text,
                'stars' => $review->stars,
                'product' => $review->product?->name,
                'created_at' => $review->created_at?->toIso8601String(),
            ])->values(),
            'avg' => $all->count() ? round($all->avg('stars'), 2) : 0,
            'count' => $all->count(),
        ]);
    }

    public function storeReview(Request $r)
    {
        $d = $r->validate([
            'name' => 'nullable|max:60',
            'text' => 'required|max:500',
            'stars' => 'required|integer|between:1,5',
            'product_id' => 'nullable|integer|exists:products,id',
        ]);
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
