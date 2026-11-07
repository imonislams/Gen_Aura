<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title', $s['name'] . ' | T-Shirt, Shirt, Pant, Panjabi in Bangladesh')</title>
  <meta name="description" content="@yield('desc', 'Premium T-shirts, shirts, pants and panjabis. Cash on delivery all over Bangladesh.')">
  <meta property="og:title" content="@yield('title', $s['name'])">
  @include('partials.head')
</head>
<body class="min-h-screen flex flex-col">
  <div class="grad text-white text-center text-sm py-1.5 font-semibold">{{ $s['top'] }}</div>
  <header class="bg-white/90 backdrop-blur border-b sticky top-0 z-10">
    <div class="max-w-6xl mx-auto px-4 py-2 flex flex-wrap items-center justify-between gap-x-3">
      <nav class="flex gap-4 md:gap-5 text-sm font-semibold order-last w-full justify-center pb-1 md:order-none md:w-auto md:pb-0">
        <a href="{{ route('home') }}">{{ __('Home') }}</a><a href="{{ route('shop') }}">{{ __('Shop') }}</a><a href="{{ route('reviews') }}">{{ __('Reviews') }}</a><a href="{{ route('contact') }}">{{ __('Contact') }}</a>
      </nav>
      @include('partials.brand')
      <div class="flex items-center gap-3 text-sm font-semibold">
        <a href="{{ route('lang', app()->getLocale() === 'en' ? 'bn' : 'en') }}" class="border rounded-full px-3 py-1">{{ app()->getLocale() === 'en' ? 'বাংলা' : 'EN' }}</a>
        @php $n = collect(session('cart', []))->sum('qty'); @endphp
        <a href="{{ route('cart') }}">{{ __('Cart') }}@if($n > 0)<span class="ml-1 grad text-white rounded-full px-2 py-0.5 text-xs">{{ $n }}</span>@endif</a>
      </div>
    </div>
  </header>
  <main class="flex-1">
    @if(session('ok'))<div class="max-w-6xl mx-auto px-4 mt-4"><div class="bg-green-50 text-green-800 rounded-xl p-3 text-sm">{{ session('ok') }}</div></div>@endif
    @if($errors->any())<div class="max-w-6xl mx-auto px-4 mt-4"><div class="bg-red-50 text-red-700 rounded-xl p-3 text-sm">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div></div>@endif
    @yield('content')
  </main>
  <footer class="bg-white border-t mt-10 py-8 px-4 text-center text-sm">
    @include('partials.brand', ['big' => false])
    <details class="max-w-md mx-auto mt-4 text-gray-600"><summary class="cursor-pointer font-semibold">{{ __('Return & Refund Policy') }}</summary><p class="mt-2">{{ __('If you receive a damaged or wrong item, contact us within 3 days of delivery for a replacement or refund.') }}</p></details>
    <a class="block mt-4 text-pur font-semibold" href="https://wa.me/{{ $s['wa'] }}">WhatsApp: +{{ $s['wa'] }}</a>
    <p class="mt-3 text-gray-400">© {{ date('Y') }} {{ $s['name'] }}</p>
  </footer>
</body>
</html>
