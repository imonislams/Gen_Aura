@extends('layouts.admin')
@section('content')
@php
  $selectedCat = old('category_id', $p->category_id ?? $categories->firstWhere('name', $p->cat)?->id);
@endphp
<form method="POST" enctype="multipart/form-data" action="{{ $p->exists ? route('admin.products.update', $p) : route('admin.products.store') }}" class="bg-white rounded-2xl p-5 grid md:grid-cols-2 gap-x-5">
  @csrf @if($p->exists) @method('PUT') @endif
  <label class="block text-sm mb-3">Name<input class="inp" name="name" value="{{ old('name', $p->name) }}" required></label>
  <label class="block text-sm mb-3">Slug (optional)<input class="inp" name="slug" value="{{ old('slug', $p->slug) }}" placeholder="auto from name"></label>
  <label class="block text-sm mb-3">SKU (optional)<input class="inp" name="sku" value="{{ old('sku', $p->sku) }}" placeholder="e.g. GA-TS-001"></label>
  <label class="block text-sm mb-3">Category<select class="inp" name="category_id">
    <option value="">Uncategorised</option>
    @foreach($categories as $c)<option value="{{ $c->id }}" @selected((string) $selectedCat === (string) $c->id)>{{ $c->name }}</option>@endforeach
  </select></label>
  <label class="block text-sm mb-3">Price (৳)<input type="number" min="0" class="inp" name="price" value="{{ old('price', $p->price) }}" required></label>
  <label class="block text-sm mb-3">Old price (৳, optional)<input type="number" min="0" class="inp" name="old_price" value="{{ old('old_price', $p->old_price) }}"></label>
  <label class="block text-sm mb-3">Stock (quantity available)<input type="number" min="0" class="inp" name="stock" value="{{ old('stock', $p->stock) }}" required></label>
  <label class="block text-sm mb-3">Status<select class="inp" name="status">
    <option value="active" @selected(old('status', $p->status) === 'active')>Active</option>
    <option value="inactive" @selected(old('status', $p->status) === 'inactive')>Inactive</option>
  </select></label>
  <label class="block text-sm mb-3">Sizes (comma separated, e.g. M,L,XL)<input class="inp" name="sizes" value="{{ old('sizes', $p->sizes) }}"></label>
  <label class="block text-sm mb-3">Subtitle (gold italic text)<input class="inp" name="sub" value="{{ old('sub', $p->sub) }}"></label>
  <label class="block text-sm mb-3">Highlights (comma separated)<input class="inp" name="feat" value="{{ old('feat', $p->feat) }}"></label>
  <label class="block text-sm mb-3 md:col-span-2">Description<textarea class="inp" rows="3" name="description">{{ old('description', $p->description) }}</textarea></label>
  <label class="text-sm flex items-center gap-2 mb-3 md:col-span-2"><input type="checkbox" name="featured" value="1" @checked(old('featured', $p->featured))> Featured product</label>
  <label class="block text-sm mb-3">Main image<input type="file" name="image" accept="image/*" class="mt-1 text-sm"></label>
  <label class="block text-sm mb-3">More images (select many)<input type="file" name="images[]" accept="image/*" multiple class="mt-1 text-sm"></label>
  @if($p->exists)
  <div class="md:col-span-2 flex gap-3 flex-wrap mb-3">
    <div><img src="{{ $p->image_url }}" alt="" class="w-16 h-16 rounded object-cover ring-2 ring-pur"><div class="text-xs text-center">Main</div></div>
    @foreach($p->images ?? [] as $img)<label class="text-xs text-center"><img src="{{ asset('storage/' . $img) }}" alt="" class="w-16 h-16 rounded object-cover"><input type="checkbox" name="remove_images[]" value="{{ $img }}"> remove</label>@endforeach
  </div>
  @endif
  <div class="flex gap-2 md:col-span-2"><button class="btn">Save</button><a href="{{ route('admin.products.index') }}" class="px-4 py-2 border rounded-full">Cancel</a></div>
</form>
@endsection
