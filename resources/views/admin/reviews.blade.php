@extends('layouts.admin')
@section('content')
@forelse($reviews as $r)
<div class="bg-white rounded-xl p-3 mb-2 flex justify-between gap-3"><span>{{ str_repeat('★', $r->stars) }} {{ $r->name }}: {{ $r->text }}</span>
  <form method="POST" action="{{ route('admin.reviews.destroy', $r) }}">@csrf @method('DELETE')<button class="text-red-500 text-sm">Delete</button></form></div>
@empty<p class="text-gray-500">No reviews yet.</p>@endforelse
@endsection
