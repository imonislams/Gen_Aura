@extends('layouts.admin')
@section('content')
<form method="GET" class="flex gap-3 mb-4 flex-wrap">
  <select class="border rounded-full px-3 py-2 text-sm" name="status" onchange="this.form.submit()"><option value="">All</option>@foreach(\App\Models\Order::STATUS as $st)<option @selected(request('status') === $st)>{{ $st }}</option>@endforeach</select>
  <input class="border rounded-full px-4 py-2 text-sm flex-1 min-w-[150px]" name="q" value="{{ request('q') }}" placeholder="Search name or phone">
</form>
<div class="space-y-3">
  @forelse($orders as $o)
  <div class="bg-white rounded-xl p-4 text-sm">
    <div class="flex justify-between flex-wrap gap-2"><b>#{{ $o->id }} · {{ $o->name }} · {{ $o->phone }}</b><span>{{ $o->created_at->format('d M Y, h:i A') }}</span></div>
    <p>{{ $o->address }}</p>
    <p class="text-gray-500">@foreach($o->items as $i){{ $i->name }}{{ $i->size ? ' (' . $i->size . ')' : '' }} ×{{ $i->qty }}{{ $loop->last ? '' : ', ' }}@endforeach</p>
    <div class="flex items-center gap-3 mt-2 flex-wrap"><b>৳{{ number_format($o->total) }}</b>
      <form method="POST" action="{{ route('admin.orders.update', $o) }}">@csrf @method('PATCH')<select name="status" class="border rounded px-2 py-1" onchange="this.form.submit()">@foreach(\App\Models\Order::STATUS as $st)<option @selected($o->status === $st)>{{ $st }}</option>@endforeach</select></form>
      <form method="POST" action="{{ route('admin.orders.destroy', $o) }}" onsubmit="return confirm('Delete this order? (Cancel it first if you want the stock returned)')">@csrf @method('DELETE')<button class="text-red-500">Delete</button></form></div>
  </div>
  @empty<p class="text-gray-500">No orders found.</p>@endforelse
</div>
<div class="mt-4">{{ $orders->links() }}</div>
@endsection
