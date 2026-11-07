@extends('layouts.admin')
@section('content')
<form method="GET" class="flex gap-3 mb-4 flex-wrap items-center">
  <a href="{{ route('admin.products.create') }}" class="btn">+ Add product</a>
  <input class="border rounded-full px-4 py-2 text-sm flex-1 min-w-[150px]" name="q" value="{{ request('q') }}" placeholder="Search products">
  <label class="text-sm flex items-center gap-2"><input type="checkbox" name="low" value="1" @checked(request('low')) onchange="this.form.submit()">Low stock only</label>
</form>
<div class="space-y-2">
  @forelse($products as $p)
  <div class="bg-white rounded-xl p-3 flex items-center gap-3">
    <img src="{{ $p->image_url }}" alt="" class="w-14 h-14 rounded-lg object-cover bg-purple-50">
    <div class="flex-1 min-w-0"><b class="block truncate">{{ $p->name }}</b><div class="text-sm text-gray-500">{{ $p->cat }} · ৳{{ number_format($p->price) }} · <span class="{{ $p->stock <= 3 ? 'text-red-600 font-semibold' : '' }}">Stock: {{ $p->stock }}</span></div></div>
    <a href="{{ route('admin.products.edit', $p) }}" class="text-pur text-sm">Edit</a>
    <form method="POST" action="{{ route('admin.products.destroy', $p) }}" onsubmit="return confirm('Delete this product?')">@csrf @method('DELETE')<button class="text-red-500 text-sm">Delete</button></form>
  </div>
  @empty<p class="text-gray-500">No products found.</p>@endforelse
</div>
@endsection
