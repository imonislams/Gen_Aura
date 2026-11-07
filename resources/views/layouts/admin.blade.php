<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin · {{ $s['name'] }}</title>
  <meta name="robots" content="noindex,nofollow">
  @include('partials.head')
</head>
<body class="min-h-screen">
  <div class="flex min-h-screen">
    <aside class="hidden md:flex md:flex-col w-64 shrink-0 bg-white border-r border-purple-100 sticky top-0 h-screen p-4">
      <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2 mb-6 px-2">
        <span class="w-8 h-8 rounded-lg grad flex items-center justify-center text-white font-serif font-bold">A</span>
        <span class="font-serif text-lg font-semibold gtext">{{ $s['name'] }}</span>
      </a>
      <nav class="flex flex-col gap-1">
        @foreach(['dashboard' => 'Dashboard', 'products.index' => 'Products', 'orders' => 'Orders', 'reviews' => 'Reviews', 'settings' => 'Settings'] as $r => $l)
          <a href="{{ route('admin.' . $r) }}" class="px-3 py-2 rounded-lg text-sm font-semibold {{ request()->routeIs('admin.' . explode('.', $r)[0] . '*') ? 'grad text-white' : 'text-gray-600 hover:bg-purple-50' }}">{{ $l }}</a>
        @endforeach
      </nav>
      <form method="POST" action="{{ route('admin.logout') }}" class="mt-auto">@csrf<button class="w-full px-3 py-2 rounded-lg text-sm font-semibold bg-red-500 text-white">Log out</button></form>
    </aside>

    <div class="flex-1 min-w-0 flex flex-col">
      <div class="md:hidden flex gap-2 p-3 overflow-x-auto bg-white border-b sticky top-0 z-20">
        @foreach(['dashboard' => 'Dashboard', 'products.index' => 'Products', 'orders' => 'Orders', 'reviews' => 'Reviews', 'settings' => 'Settings'] as $r => $l)
          <a href="{{ route('admin.' . $r) }}" class="whitespace-nowrap px-4 py-2 rounded-full text-sm font-semibold {{ request()->routeIs('admin.' . explode('.', $r)[0] . '*') ? 'grad text-white' : 'bg-white border' }}">{{ $l }}</a>
        @endforeach
        <form method="POST" action="{{ route('admin.logout') }}" class="ml-auto">@csrf<button class="whitespace-nowrap px-4 py-2 rounded-full text-sm font-semibold bg-red-500 text-white">Log out</button></form>
      </div>

      <main class="p-4 w-full max-w-5xl mx-auto">
        @if(session('ok'))<div class="bg-green-50 text-green-800 rounded-xl p-3 mb-4 text-sm">{{ session('ok') }}</div>@endif
        @if($errors->any())<div class="bg-red-50 text-red-700 rounded-xl p-3 mb-4 text-sm">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif
        @yield('content')
      </main>
    </div>
  </div>
</body>
</html>
