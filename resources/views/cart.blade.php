@extends('layouts.app')
@section('title', __('Cart') . ' · ' . $s['name'])
@section('content')
@if(!count($lines))
  <div class="text-center p-16 text-gray-500">{{ __('Your bag is empty.') }} <a href="{{ route('shop') }}" class="text-pur font-semibold">{{ __('Browse the shop') }}</a></div>
@else
<div class="max-w-3xl mx-auto p-4 grid md:grid-cols-2 gap-6 items-start">
  <div class="space-y-3">
    @foreach($lines as $l)
    <div class="bg-white rounded-xl p-3 flex gap-3 items-center">
      <img src="{{ $l['p']->image_url }}" alt="" class="w-16 h-16 rounded-lg object-cover">
      <div class="flex-1">
        <b class="text-sm">{{ $l['p']->name }}{{ $l['size'] ? ' (' . $l['size'] . ')' : '' }}</b>
        <div class="text-sm">৳{{ number_format($l['p']->price) }}</div>
      </div>
      <div class="flex flex-col items-end gap-1">
        <form method="POST" action="{{ route('cart.update') }}" class="flex items-center gap-2">@csrf
          <input type="hidden" name="key" value="{{ $l['key'] }}">
          <button name="qty" value="{{ $l['qty'] - 1 }}" class="w-7 h-7 rounded-full border">−</button>
          <span>{{ $l['qty'] }}</span>
          <button name="qty" value="{{ $l['qty'] + 1 }}" class="w-7 h-7 rounded-full border">+</button>
        </form>
        <form method="POST" action="{{ route('cart.remove') }}">@csrf
          <input type="hidden" name="key" value="{{ $l['key'] }}">
          <button class="text-xs text-red-500">{{ __('Remove') }}</button>
        </form>
      </div>
    </div>
    @endforeach
    <a href="{{ route('shop') }}" class="inline-block text-sm text-pur font-semibold">← {{ __('Continue shopping') }}</a>
  </div>

  <div class="bg-white rounded-2xl p-5">
    <h3 class="font-semibold mb-3">{{ __('Order summary') }}</h3>
    <div class="text-sm flex justify-between py-1"><span>{{ __('Subtotal') }}</span><span>৳{{ number_format($sub) }}</span></div>
    <div class="text-sm flex justify-between py-1"><span>{{ __('Delivery') }}</span><span>৳{{ number_format($ship) }}</span></div>
    <div class="font-bold flex justify-between text-lg border-t mt-2 pt-3"><span>{{ __('Total') }}</span><span>৳{{ number_format($total) }}</span></div>
    <a href="{{ route('checkout.show') }}" class="btn w-full mt-4">{{ __('Proceed to checkout') }}</a>
    <p class="text-xs text-gray-400 text-center mt-2">{{ __('Cash on Delivery · totals re-verified on the server') }}</p>
  </div>
</div>
@endif
@endsection
