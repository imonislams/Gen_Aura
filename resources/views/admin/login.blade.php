<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Admin login</title><meta name="robots" content="noindex,nofollow">@include('partials.head')</head>
<body class="min-h-screen">
  <form method="POST" action="{{ route('admin.login.post') }}" class="max-w-sm mx-auto p-6 mt-16 bg-white rounded-2xl shadow">@csrf
    <h2 class="font-serif text-3xl mb-3">Admin</h2>
    <input class="inp" name="username" value="{{ old('username') }}" placeholder="Username" autofocus>
    <input class="inp mt-3" type="password" name="password" placeholder="Password">
    @if($errors->any())<p class="text-red-600 text-sm mt-2">{{ $errors->first() }}</p>@endif
    <button class="btn w-full mt-3">Log in</button>
  </form>
</body>
</html>
