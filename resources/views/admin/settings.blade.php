@extends('layouts.admin')
@section('content')
<form method="POST" action="{{ route('admin.settings.save') }}" enctype="multipart/form-data" class="bg-white rounded-2xl p-5 grid md:grid-cols-2 gap-x-5 mb-6">@csrf
  @foreach(['name' => 'Store name', 'ar' => 'Arabic name', 'top' => 'Top bar text', 'title' => 'Hero title', 'sub' => 'Hero subtitle', 'wa' => 'WhatsApp number (880…)', 'ship' => 'Delivery charge (৳)', 'cats' => 'Categories (comma separated)', 'fb' => 'Facebook page link'] as $k => $l)
    <label class="block text-sm mb-3">{{ $l }}<input class="inp" name="{{ $k }}" value="{{ old($k, $s[$k]) }}"></label>
  @endforeach
  <div class="md:col-span-2">
    <label class="block text-sm mb-2">Hero card image (jpg/png/webp, max 4MB)<input type="file" name="hero_image" accept="image/jpeg,image/png,image/webp" class="inp"></label>
    @if(!empty($s['hero_image']))
      <div class="flex items-center gap-3 mb-3">
        <img src="{{ asset('storage/' . $s['hero_image']) }}" alt="Hero" class="w-24 h-24 rounded-xl object-cover border">
        <span class="text-xs text-gray-500">Current image (upload a new one to replace)</span>
      </div>
    @endif
  </div>
  <div class="md:col-span-2"><button class="btn">Save settings</button></div>
</form>
<form method="POST" action="{{ route('admin.password') }}" class="bg-white rounded-2xl p-5 grid md:grid-cols-3 gap-x-5">@csrf
  <h3 class="font-semibold md:col-span-3 mb-2">Change admin login</h3>
  <label class="block text-sm mb-3">Username<input class="inp" name="username" value="{{ \App\Models\Admin::find(session('admin_id'))->username }}" required></label>
  <label class="block text-sm mb-3">Current password<input type="password" class="inp" name="current" required></label>
  <label class="block text-sm mb-3">New password (min 6)<input type="password" class="inp" name="new_password" required></label>
  <div class="md:col-span-3"><button class="btn">Update login</button></div>
</form>
@endsection
