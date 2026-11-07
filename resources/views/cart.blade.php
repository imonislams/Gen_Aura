@extends('layouts.app')
@section('title', __('Cart') . ' · ' . $s['name'])
@section('content')
@if(session('done'))
  <div class="max-w-md mx-auto text-center p-10"><p class="text-lg">{{ __('Order placed! We will call you to confirm.') }}</p><a class="btn mt-4" href="https://wa.me/{{ $s['wa'] }}">{{ __('Chat on WhatsApp') }}</a></div>
@elseif(!count($lines))
  <div class="text-center p-16 text-gray-500">{{ __('Your bag is empty.') }} <a href="{{ route('shop') }}" class="text-pur font-semibold">{{ __('Browse the shop') }}</a></div>
@else
<div class="max-w-3xl mx-auto p-4 grid md:grid-cols-2 gap-6">
  <div class="space-y-3">
    @foreach($lines as $l)
    <div class="bg-white rounded-xl p-3 flex gap-3 items-center">
      <img src="{{ $l['p']->image_url }}" alt="" class="w-16 h-16 rounded-lg object-cover">
      <div class="flex-1"><b class="text-sm">{{ $l['p']->name }}{{ $l['size'] ? ' (' . $l['size'] . ')' : '' }}</b><div class="text-sm">৳{{ number_format($l['p']->price) }}</div></div>
      <form method="POST" action="{{ route('cart.update') }}" class="flex items-center gap-2">@csrf<input type="hidden" name="key" value="{{ $l['key'] }}">
        <button name="qty" value="{{ $l['qty'] - 1 }}" class="w-7 h-7 rounded-full border">−</button><span>{{ $l['qty'] }}</span><button name="qty" value="{{ $l['qty'] + 1 }}" class="w-7 h-7 rounded-full border">+</button></form>
    </div>
    @endforeach
    <div class="text-sm flex justify-between"><span>{{ __('Delivery') }}</span><span>৳{{ number_format($ship) }}</span></div>
    <div class="font-bold flex justify-between text-lg"><span>{{ __('Total') }}</span><span>৳{{ number_format($total) }}</span></div>
  </div>
  <form method="POST" action="{{ route('cart.checkout') }}" class="bg-white rounded-2xl p-5">@csrf
    <label class="block text-sm mb-3">{{ __('Full name') }}<input class="inp" name="name" value="{{ old('name') }}" required></label>
    <label class="block text-sm mb-3">{{ __('Phone') }}<input class="inp" name="phone" value="{{ old('phone') }}" required></label>
    <label class="block text-sm mb-3">{{ __('Full address') }}<textarea class="inp" rows="3" name="address" required>{{ old('address') }}</textarea></label>
    <button class="btn w-full">{{ __('Place order (Cash on Delivery)') }}</button>
  </form>
</div>
@endif
@endsection
