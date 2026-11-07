<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index(Request $r)
    {
        $q = Product::query();
        if ($r->filled('q')) $q->where('name', 'like', '%' . $r->q . '%');
        if ($r->boolean('low')) $q->where('stock', '<=', 3);
        return view('admin.products.index', ['products' => $q->latest()->get()]);
    }

    public function create() { return view('admin.products.form', ['p' => new Product(['stock' => 10]), 'cats' => $this->cats()]); }
    public function edit(Product $product) { return view('admin.products.form', ['p' => $product, 'cats' => $this->cats()]); }

    public function store(Request $r)
    {
        Product::create($this->payload($r, new Product()));
        return redirect()->route('admin.products.index')->with('ok', 'Product added');
    }

    public function update(Request $r, Product $product)
    {
        $product->update($this->payload($r, $product));
        return redirect()->route('admin.products.index')->with('ok', 'Product updated');
    }

    public function destroy(Product $product)
    {
        foreach (array_merge([$product->image], $product->images ?? []) as $f) if ($f) Storage::disk('public')->delete($f);
        $product->delete();
        return back()->with('ok', 'Product deleted');
    }

    private function cats(): array { return array_values(array_filter(array_map('trim', explode(',', Setting::all_()['cats'])))); }

    private function payload(Request $r, Product $p): array
    {
        $d = $r->validate([
            'name' => 'required|max:120', 'cat' => 'nullable|max:40', 'sub' => 'nullable|max:80', 'sizes' => 'nullable|max:80', 'feat' => 'nullable|max:200',
            'description' => 'nullable|max:2000', 'price' => 'required|integer|min:0', 'old_price' => 'nullable|integer|min:0', 'stock' => 'required|integer|min:0',
            'image' => 'nullable|image|max:4096', 'images.*' => 'image|max:4096',
        ]);
        unset($d['image'], $d['images']);
        $d['old_price'] = $d['old_price'] ?? 0;
        $d['image'] = $p->image;
        if ($r->hasFile('image')) {
            if ($p->image) Storage::disk('public')->delete($p->image);
            $d['image'] = $r->file('image')->store('products', 'public');
        }
        $keep = array_values(array_diff($p->images ?? [], $r->input('remove_images', [])));
        foreach (array_diff($p->images ?? [], $keep) as $f) Storage::disk('public')->delete($f);
        foreach ($r->file('images', []) as $f) $keep[] = $f->store('products', 'public');
        $d['images'] = $keep;
        return $d;
    }
}
