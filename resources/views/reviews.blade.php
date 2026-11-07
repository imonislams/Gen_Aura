@extends('layouts.app')
@section('title', __('Reviews') . ' · ' . $s['name'])
@section('content')
<section class="max-w-4xl mx-auto px-4 py-8">
  <h2 class="font-serif text-3xl">{{ __('Customers are saying') }}</h2><p class="text-org font-semibold mb-4">{{ $avg }} ★ ({{ $reviews->count() }})</p>
  <form method="POST" action="{{ route('reviews.store') }}" class="bg-white rounded-2xl p-5 mb-6">@csrf
    <h3 class="font-semibold mb-2">{{ __('Write a review') }}</h3>
    <div class="grid md:grid-cols-2 gap-3"><input class="inp" name="name" value="{{ old('name') }}" placeholder="{{ __('Your name') }}">
      <select class="inp" name="stars">@foreach([5,4,3,2,1] as $n)<option value="{{ $n }}">{{ str_repeat('★', $n) }}</option>@endforeach</select></div>
    <textarea class="inp mt-3" rows="3" name="text" placeholder="{{ __('Your review') }}" required>{{ old('text') }}</textarea>
    <button class="btn mt-3">{{ __('Submit review') }}</button>
  </form>
  <div class="grid md:grid-cols-2 gap-4">@foreach($reviews as $r)<div class="bg-white rounded-2xl p-5 shadow-sm"><div class="text-org">{{ str_repeat('★', $r->stars) }}</div><p class="my-2">{{ $r->text }}</p><b class="text-sm text-gray-500">{{ $r->name }}</b></div>@endforeach</div>
</section>
@endsection
