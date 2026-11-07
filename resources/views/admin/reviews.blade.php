@extends('layouts.admin')
@section('content')
<form method="GET" class="flex gap-3 mb-4 flex-wrap items-center">
  <input class="border rounded-full px-4 py-2 text-sm flex-1 min-w-[150px]" name="q" value="{{ request('q') }}" placeholder="Search name or text">
  <select name="status" class="border rounded-full px-3 py-2 text-sm" onchange="this.form.submit()">
    <option value="">All statuses</option>
    @foreach(['pending', 'approved', 'rejected'] as $st)
      <option value="{{ $st }}" @selected(request('status') === $st)>{{ ucfirst($st) }}</option>
    @endforeach
  </select>
  <button class="btn !py-1 !px-4 text-sm">Filter</button>
</form>
@forelse($reviews as $r)
<div class="bg-white rounded-xl p-3 mb-2">
  <div class="flex justify-between gap-3 flex-wrap">
    <div>
      <div class="text-org">{{ str_repeat('★', $r->stars) }}</div>
      <b>{{ $r->name }}</b>
      @if($r->product)<span class="text-sm text-gray-500">· {{ $r->product->name }}</span>@endif
      <span class="ml-2 text-xs px-2 py-0.5 rounded-full {{ $r->status === 'approved' ? 'bg-green-50 text-green-700' : ($r->status === 'rejected' ? 'bg-red-50 text-red-600' : 'bg-amber-50 text-amber-700') }}">{{ $r->status }}</span>
      <p class="mt-1">{{ $r->text }}</p>
    </div>
    <div class="flex items-center gap-2">
      @if($r->status !== 'approved')
        <form method="POST" action="{{ route('admin.reviews.status', $r) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="approved"><button class="text-green-600 text-sm font-semibold">Approve</button></form>
      @endif
      @if($r->status !== 'rejected')
        <form method="POST" action="{{ route('admin.reviews.status', $r) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="rejected"><button class="text-amber-600 text-sm font-semibold">Reject</button></form>
      @endif
      <form method="POST" action="{{ route('admin.reviews.destroy', $r) }}">@csrf @method('DELETE')<button class="text-red-500 text-sm">Delete</button></form>
    </div>
  </div>
</div>
@empty
<p class="text-gray-500">No reviews yet.</p>
@endforelse
<div class="mt-4">{{ $reviews->links() }}</div>
@endsection
