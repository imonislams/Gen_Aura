@extends('layouts.app')
@section('title', $p->name . ' · ' . $s['name'])
@section('desc', \Illuminate\Support\Str::limit($p->description, 155))
@section('content')
@php $g = $p->gallery; @endphp
@php $ld = json_encode(['@context' => 'https://schema.org', '@type' => 'Product', 'name' => $p->name, 'description' => $p->description, 'category' => $p->cat, 'offers' => ['@type' => 'Offer', 'priceCurrency' => 'BDT', 'price' => $p->price, 'availability' => $p->stock > 0 ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock']], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG); @endphp
<script type="application/ld+json">{!! $ld !!}</script>
<section class="max-w-6xl mx-auto px-4 py-8">
  <a href="{{ route('shop') }}" class="text-sm text-pur font-semibold">{{ __('← Back to shop') }}</a>
  <div class="grid md:grid-cols-2 gap-8 mt-4">
    <div>
      <img id="main" src="{{ $g[0] }}" alt="{{ $p->name }}" class="w-full aspect-square object-cover rounded-2xl shadow">
      @if(count($g) > 1)<div class="flex gap-2 mt-3 flex-wrap">@foreach($g as $img)<img src="{{ $img }}" alt="" onclick="document.getElementById('main').src=this.src" class="w-16 h-16 rounded-lg object-cover cursor-pointer border-2 border-transparent hover:border-pur">@endforeach</div>@endif
    </div>
    <div>
      <span class="inline-block text-xs font-bold bg-white border border-gold text-gold rounded-full px-3 py-1">{{ $p->cat }}</span>
      <span class="inline-block text-xs font-bold bg-white border rounded-full px-3 py-1">🚚 {{ __('COD · All Bangladesh') }}</span>
      <h1 class="font-serif text-4xl md:text-5xl font-bold mt-3">{{ $p->name }}</h1>
      @if($p->sub)<p class="font-serif italic text-3xl gtext">{{ $p->sub }}</p>@endif
      <p class="text-gray-600 mt-3">{{ $p->description }}</p>
      @if(count($p->feat_list))<div class="flex gap-4 flex-wrap my-5">@foreach($p->feat_list as $f)<div class="w-20 text-center text-xs font-semibold"><div class="w-14 h-14 mx-auto mb-1 rounded-full border-2 border-gold bg-amber-50 flex items-center justify-center text-gold text-xl">✦</div>{{ $f }}</div>@endforeach</div>@endif
      <div class="flex items-baseline gap-3 my-4"><b class="text-2xl">৳{{ number_format($p->price) }}</b>@if($p->old_price > 0)<s class="text-gray-400">৳{{ number_format($p->old_price) }}</s>@endif</div>
      @if($p->stock <= 0)
        <p class="w-full text-center rounded-xl py-3 font-semibold bg-gray-200 text-gray-500">{{ __('Out of stock') }}</p>
      @else
        <form method="POST" action="{{ route('cart.add', $p) }}">@csrf
          @if(count($p->size_list))<div class="mb-4"><div class="text-sm font-semibold mb-1">{{ __('Size') }}</div><div class="flex gap-2 flex-wrap">
            @foreach($p->size_list as $i => $z)<label><input type="radio" name="size" value="{{ $z }}" class="peer sr-only" @checked($i === 0)><span class="inline-block cursor-pointer px-4 py-1.5 rounded-lg border text-sm font-semibold bg-white peer-checked:bg-pur peer-checked:text-white peer-checked:border-pur">{{ $z }}</span></label>@endforeach</div></div>@endif
          @if($p->stock <= 5)<p class="text-sm text-mag font-semibold mb-2">{{ $p->stock }} {{ __('left') }}</p>@endif
          <button name="go" value="bag" class="w-full border border-gray-400 rounded-xl py-3 font-semibold bg-white">👉 {{ __('Add to bag') }}</button>
          <button name="go" value="buy" class="w-full mt-3 rounded-xl py-3 font-semibold bg-black text-white">{{ __('Buy it now') }}</button>
        </form>
      @endif
      <div class="grid grid-cols-3 gap-2 text-center text-xs font-bold text-gray-600 my-6"><div class="text-2xl">🚚<p class="text-xs mt-1">{{ __('Shipped from Dhaka') }}</p></div><div class="text-2xl">↩️<p class="text-xs mt-1">{{ __('Easy Returns') }}</p></div><div class="text-2xl">🤍<p class="text-xs mt-1">{{ __('Quality Checked') }}</p></div></div>
      <details class="border-t"><summary class="py-4 font-semibold cursor-pointer">🚚 {{ __('Shipping') }}</summary><p class="pb-4 text-gray-600 text-sm">{{ __('Delivery within 3 days all over Bangladesh. Delivery charge:') }} ৳{{ $s['ship'] }}</p></details>
      <details class="border-t border-b"><summary class="py-4 font-semibold cursor-pointer">↩️ {{ __('Return & Refund Policy') }}</summary><p class="pb-4 text-gray-600 text-sm">{{ __('If you receive a damaged or wrong item, contact us within 3 days of delivery for a replacement or refund.') }}</p></details>
    </div>
  </div>
</section>
@endsection
