@php $out = $p->stock <= 0; @endphp
<a href="{{ route('product', $p) }}" class="bg-white rounded-2xl shadow-sm overflow-hidden flex flex-col hover:shadow-md transition">
  <div class="relative">
    <img src="{{ $p->image_url }}" alt="{{ $p->name }}" class="w-full aspect-square object-cover {{ $out ? 'opacity-50' : '' }}">
    @if($p->off > 0 && !$out)<span class="absolute top-2 left-2 grad text-white text-xs font-bold rounded-full px-2 py-1">-{{ $p->off }}%</span>@endif
    @if($out)<span class="absolute top-2 right-2 bg-black text-white text-xs font-bold rounded-full px-2 py-1">{{ __('Out of stock') }}</span>
    @elseif($p->stock <= 5)<span class="absolute bottom-2 left-2 bg-white/90 text-mag text-xs font-bold rounded-full px-2 py-1">{{ $p->stock }} {{ __('left') }}</span>@endif
  </div>
  <div class="p-4 flex flex-col flex-1">
    <span class="text-xs text-mag font-semibold">{{ $p->cat }}</span>
    <h3 class="font-semibold">{{ $p->name }}</h3>
    <p class="text-sm text-gray-500 line-clamp-2 flex-1">{{ $p->description }}</p>
    <div class="mt-2 flex items-baseline gap-2"><b class="text-lg">৳{{ number_format($p->price) }}</b>@if($p->old_price > 0)<s class="text-gray-400 text-sm">৳{{ number_format($p->old_price) }}</s>@endif</div>
  </div>
</a>
