@extends('layouts.app')
@section('content')
@php
  $hero = $products->first();
  $heroImg = !empty($s['hero_image']) ? asset('storage/' . $s['hero_image']) : ($hero ? $hero->image_url : null);
@endphp
<section class="max-w-6xl mx-auto px-4 pt-8">
  <div class="relative overflow-hidden rounded-3xl grad text-white shadow-xl">
    <div class="absolute -top-20 -right-20 w-72 h-72 rounded-full bg-white/10"></div>
    <div class="absolute -bottom-28 -left-16 w-80 h-80 rounded-full bg-white/10"></div>
    <div class="relative grid md:grid-cols-2 items-center gap-6 p-8 md:p-12">
      <div>
        <span class="inline-block bg-white/20 backdrop-blur rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-wide">{{ $s['top'] ?: 'New Collection' }}</span>
        <h1 class="font-serif text-4xl md:text-6xl font-bold mt-4">{{ $s['title'] }}</h1>
        <p class="mt-4 text-white/85 max-w-md">{{ $s['sub'] }}</p>
        <div class="mt-6 flex flex-wrap gap-3">
          <a href="{{ route('shop') }}" class="bg-white text-mag font-semibold rounded-full px-6 py-3 hover:bg-white/90 transition">{{ __('Shop now') }}</a>
          <a href="{{ route('reviews') }}" class="border border-white/60 rounded-full px-6 py-3 font-semibold hover:bg-white/10 transition">{{ __('Reviews') }}</a>
        </div>
      </div>
      @if($heroImg)
        <div class="relative hidden md:block">
          <img src="{{ $heroImg }}" alt="{{ $s['name'] }}" class="w-full max-w-sm mx-auto aspect-square object-cover rounded-2xl shadow-2xl rotate-2">
          @if(empty($s['hero_image']))
            <div class="absolute bottom-3 left-1/2 -translate-x-1/2 bg-white text-gray-900 rounded-xl px-4 py-2 text-sm font-semibold shadow whitespace-nowrap">{{ $hero->name }} · ৳{{ number_format($hero->price) }}</div>
          @endif
        </div>
      @endif
    </div>
  </div>
</section>
<section class="max-w-6xl mx-auto px-4 py-8"><h2 class="font-serif text-3xl mb-4">{{ __('All products') }}</h2>
  @if($products->count())<div class="grid grid-cols-2 md:grid-cols-4 gap-4">@foreach($products as $p)@include('partials.card')@endforeach</div>
  @else<p class="text-gray-500">{{ __('No products yet.') }}</p>@endif</section>
@if($reviews->count())
<section class="max-w-6xl mx-auto px-4 py-8"><h2 class="font-serif text-3xl mb-4">{{ __('Customers are saying') }} <a href="{{ route('reviews') }}" class="text-sm text-pur font-semibold">{{ __('All reviews') }}</a></h2>
  <div class="grid md:grid-cols-3 gap-4">@foreach($reviews as $r)<div class="bg-white rounded-2xl p-5 shadow-sm"><div class="text-org">{{ str_repeat('★', $r->stars) }}</div><p class="my-2">{{ $r->text }}</p><b class="text-sm text-gray-500">{{ $r->name }}</b></div>@endforeach</div></section>
@endif
@endsection
