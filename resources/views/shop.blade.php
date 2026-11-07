@extends('layouts.app')
@section('title', __('Shop') . ' · ' . $s['name'])
@section('content')
@php $cats = array_values(array_filter(array_map('trim', explode(',', $s['cats'])))); @endphp
<section class="max-w-6xl mx-auto px-4 py-8">
  <h2 class="font-serif text-3xl mb-4">{{ __('All products') }}</h2>
  <div class="flex gap-2 flex-wrap mb-4">
    <a href="{{ route('shop', request()->except('cat', 'page')) }}" class="px-4 py-1.5 rounded-full text-sm font-semibold {{ request('cat') ? 'bg-white border' : 'grad text-white' }}">{{ __('All') }}</a>
    @foreach($cats as $c)<a href="{{ route('shop', array_merge(request()->except('page'), ['cat' => $c])) }}" class="px-4 py-1.5 rounded-full text-sm font-semibold {{ request('cat') === $c ? 'grad text-white' : 'bg-white border' }}">{{ $c }}</a>@endforeach
  </div>
  <form method="GET" class="bg-white rounded-2xl p-3 mb-5 grid grid-cols-2 md:grid-cols-5 gap-2 items-center">
    <input type="hidden" name="cat" value="{{ request('cat') }}">
    <input class="inp !mt-0 col-span-2" name="q" value="{{ request('q') }}" placeholder="{{ __('Search products') }}">
    <input type="number" class="inp !mt-0" name="min" value="{{ request('min') }}" placeholder="{{ __('Min ৳') }}">
    <input type="number" class="inp !mt-0" name="max" value="{{ request('max') }}" placeholder="{{ __('Max ৳') }}">
    <select class="inp !mt-0" name="sort"><option value="new">{{ __('Newest') }}</option><option value="low" @selected(request('sort') === 'low')>{{ __('Price: Low to High') }}</option><option value="high" @selected(request('sort') === 'high')>{{ __('Price: High to Low') }}</option></select>
    <label class="text-sm flex items-center gap-2 col-span-2"><input type="checkbox" name="stock" value="1" @checked(request('stock'))>{{ __('In stock only') }}</label>
    <button class="btn col-span-2 md:col-span-2">{{ __('Apply filters') }}</button>
  </form>
  @if($products->count())<div class="grid grid-cols-2 md:grid-cols-4 gap-4">@foreach($products as $p)@include('partials.card')@endforeach</div>
  @else<p class="text-gray-500">{{ __('No products found.') }}</p>@endif
  <div class="mt-6">{{ $products->links() }}</div>
</section>
@endsection
