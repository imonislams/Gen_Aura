@extends('layouts.admin')
@section('content')
<form method="GET" class="flex gap-3 mb-4 flex-wrap items-center">
  <select class="border rounded-full px-3 py-2 text-sm" name="status" onchange="this.form.submit()">
    <option value="">All statuses</option>
    @foreach(\App\Models\Order::STATUS as $st)<option @selected(request('status') === $st)>{{ $st }}</option>@endforeach
  </select>
  <input class="border rounded-full px-4 py-2 text-sm flex-1 min-w-[150px]" name="q" value="{{ request('q') }}" placeholder="Search order number, name or phone">
  <button class="btn !py-1 !px-4 text-sm">Filter</button>
</form>
<div class="space-y-3">
  @forelse($orders as $o)
  <div class="bg-white rounded-xl p-4 text-sm">
    <div class="flex justify-between flex-wrap gap-2">
      <b>{{ $o->order_number ?? '#' . $o->id }} · {{ $o->name }} · {{ $o->phone }}</b>
      <span>{{ $o->created_at->format('d M Y, h:i A') }}</span>
    </div>
    <p>{{ $o->address }}@if($o->delivery_area) · {{ $o->delivery_area }}@endif</p>
    <p class="text-gray-500">@foreach($o->items as $i){{ $i->name }}{{ $i->size ? ' (' . $i->size . ')' : '' }} ×{{ $i->qty }}{{ $loop->last ? '' : ', ' }}@endforeach</p>
    <div class="flex items-center gap-3 mt-2 flex-wrap">
      <b>৳{{ number_format($o->total) }}</b>
      <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100">{{ $o->payment_method }}</span>
      <form method="POST" action="{{ route('admin.orders.update', $o) }}">@csrf @method('PATCH')
        <select name="status" class="border rounded px-2 py-1" onchange="this.form.submit()">@foreach(\App\Models\Order::STATUS as $st)<option @selected($o->status === $st)>{{ $st }}</option>@endforeach</select>
      </form>
      <a href="{{ route('admin.orders.show', $o) }}" class="text-pur text-sm font-semibold">View</a>
      <a href="{{ route('admin.orders.invoice', $o) }}" class="text-pur text-sm font-semibold">Invoice</a>
    </div>
  </div>
  @empty
  <p class="text-gray-500">No orders found.</p>
  @endforelse
</div>
<div class="mt-4">{{ $orders->links() }}</div>
@endsection
