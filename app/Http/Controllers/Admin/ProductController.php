<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(Request $r)
    {
        $q = Product::with('category');

        if ($r->filled('q')) {
            $q->where(fn ($x) => $x->where('name', 'like', '%' . $r->q . '%')->orWhere('sku', 'like', '%' . $r->q . '%'));
        }
        if ($r->boolean('low')) {
            $q->where('stock', '<=', 3);
        }
        if ($r->filled('status')) {
            $q->where('status', $r->status);
        }
        if ($r->boolean('trashed')) {
            $q->onlyTrashed();
        }

        return view('admin.products.index', [
            'products' => $q->latest()->paginate(30)->withQueryString(),
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        return view('admin.products.form', [
            'p' => new Product(['stock' => 10, 'status' => 'active']),
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function edit(Product $product)
    {
        return view('admin.products.form', [
            'p' => $product,
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

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
        $product->delete();

        return back()->with('ok', 'Product deleted (it can be restored)');
    }

    public function restore(Product $product)
    {
        $product->restore();

        return back()->with('ok', 'Product restored');
    }

    private function payload(Request $r, Product $p): array
    {
        $d = $r->validate([
            'name' => 'required|max:120',
            'slug' => ['nullable', 'max:140', Rule::unique('products', 'slug')->ignore($p->id)],
            'sku' => ['nullable', 'max:60', Rule::unique('products', 'sku')->ignore($p->id)],
            'category_id' => ['nullable', 'exists:categories,id'],
            'cat' => 'nullable|max:40',
            'sub' => 'nullable|max:80',
            'sizes' => 'nullable|max:80',
            'feat' => 'nullable|max:200',
            'description' => 'nullable|max:2000',
            'price' => 'required|integer|min:0',
            'old_price' => 'nullable|integer|min:0',
            'stock' => 'required|integer|min:0',
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'featured' => 'nullable|boolean',
            'image' => 'nullable|image|max:4096',
            'images.*' => 'image|max:4096',
            'remove_images' => 'nullable|array',
            'remove_images.*' => 'string',
        ]);

        unset($d['image'], $d['images'], $d['remove_images']);

        $d['old_price'] = $d['old_price'] ?? 0;
        $d['featured'] = (bool) ($d['featured'] ?? false);
        $d['status'] = $d['status'] ?? 'active';

        if (array_key_exists('category_id', $d)) {
            if (!empty($d['category_id'])) {
                $category = Category::find($d['category_id']);
                $d['cat'] = $category?->name;
            } else {
                $d['cat'] = null;
            }
        }

        $d['slug'] = !empty($d['slug'] ?? null)
            ? (Str::slug($d['slug']) ?: $this->uniqueSlug($d['name'], $p->id))
            : $this->uniqueSlug($d['name'], $p->id);

        if (empty($d['sku'] ?? null)) {
            $d['sku'] = null;
        }

        $d['image'] = $p->image;
        if ($r->hasFile('image')) {
            if ($p->image) {
                Storage::disk('public')->delete($p->image);
            }
            $d['image'] = $r->file('image')->store('products', 'public');
        }

        $keep = array_values(array_diff($p->images ?? [], $r->input('remove_images', [])));
        foreach (array_diff($p->images ?? [], $keep) as $f) {
            Storage::disk('public')->delete($f);
        }
        foreach ($r->file('images', []) as $f) {
            $keep[] = $f->store('products', 'public');
        }
        $d['images'] = $keep;

        return $d;
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'product';
        $slug = $base;
        $counter = 2;

        while (
            Product::withTrashed()
                ->where('slug', $slug)
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base . '-' . $counter++;
        }

        return $slug;
    }
}
