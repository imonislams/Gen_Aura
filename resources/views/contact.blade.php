@extends('layouts.app')
@section('title', __('Contact us') . ' · ' . $s['name'])
@section('content')
<section class="max-w-xl mx-auto px-4 py-12 text-center">
  <h2 class="font-serif text-4xl gtext">{{ __('Contact us') }}</h2><p class="text-gray-600 my-3">{{ __('Questions? Do not hesitate to reach us.') }}</p>
  <a class="btn" href="https://wa.me/{{ $s['wa'] }}">{{ __('Chat on WhatsApp') }}: +{{ $s['wa'] }}</a>
  @if($s['fb'])<a class="block mt-4 text-pur font-semibold" href="{{ $s['fb'] }}">Facebook: {{ $s['name'] }}</a>@endif
</section>
@endsection
