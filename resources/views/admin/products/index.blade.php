@extends('layouts.admin')
@section('content')
<form method="GET" class="flex gap-3 mb-4 flex-wrap items-center">
  <a href="{{ route('admin.products.create') }}" class="btn">+ Add product</a>
  <input class="border rounded-full px-4 py-2 text-sm flex-1 min-w-[150px]" name="q" value="{{ request('q') }}" placeholder="Search name or SKU">
  <select name="status" class="border rounded-full px-3 py-2 text-sm" onchange="this.form.submit()">
    <option value="">All statuses</option>
    <option value="active" @selected(request('status') === 'active')>Active</option>
    <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
  </select>
  <label class="text-sm flex items-center gap-2"><input type="checkbox" name="low" value="1" @checked(request('low')) onchange="this.form.submit()">Low stock only</label>
  <label class="text-sm flex items-center gap-2"><input type="checkbox" name="trashed" value="1" @checked(request('trashed')) onchange="this.form.submit()">Deleted only</label>
</form>
<div class="space-y-2">
  @forelse($products as $p)
  <div class="bg-white rounded-xl p-3 flex items-center gap-3">
    <img src="{{ $p->image_url }}" alt="" class="w-14 h-14 rounded-lg object-cover bg-purple-50">
    <div class="flex-1 min-w-0">
      <b class="block truncate">{{ $p->name }}</b>
      <div class="text-sm text-gray-500">
        {{ $p->category?->name ?? ($p->cat ?: 'Uncategorised') }}
        · ৳{{ number_format($p->price) }}
        · <span class="{{ $p->stock <= 3 ? 'text-red-600 font-semibold' : '' }}">Stock: {{ $p->stock }}</span>
        @if($p->sku)<span class="ml-1">· SKU: {{ $p->sku }}</span>@endif
      </div>
      <div class="text-xs text-gray-400 mt-0.5">
        <span class="inline-block px-2 py-0.5 rounded-full {{ $p->status === 'active' ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500' }}">{{ $p->status }}</span>
        @if($p->featured)<span class="ml-1 inline-block px-2 py-0.5 rounded-full bg-amber-50 text-amber-700">featured</span>@endif
        @if($p->trashed())<span class="ml-1 inline-block px-2 py-0.5 rounded-full bg-red-50 text-red-600">deleted {{ $p->deleted_at?->format('d M Y') }}</span>@endif
      </div>
    </div>
    @if($p->trashed())
      <form method="POST" action="{{ route('admin.products.restore', $p) }}">@csrf @method('PATCH')<button class="text-green-600 text-sm font-semibold">Restore</button></form>
    @else
      <a href="{{ route('admin.products.edit', $p) }}" class="text-pur text-sm">Edit</a>
      <form method="POST" action="{{ route('admin.products.destroy', $p) }}" onsubmit="return confirm('Delete this product? It can be restored.')">@csrf @method('DELETE')<button class="text-red-500 text-sm">Delete</button></form>
    @endif
  </div>
  @empty
  <p class="text-gray-500">No products found.</p>
  @endforelse
</div>
<div class="mt-4">{{ $products->links() }}</div>
@endsection
