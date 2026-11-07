<a href="{{ route('home') }}" class="flex flex-col items-center leading-none">
  <svg width="{{ ($big ?? false) ? 110 : 40 }}" height="{{ ($big ?? false) ? 110 : 40 }}" viewBox="0 0 120 120"><defs><linearGradient id="lg" x1=".1" y1="0" x2=".9" y2="1"><stop offset="0" stop-color="#8E3FD0"/><stop offset=".55" stop-color="#B03A9A"/><stop offset="1" stop-color="#E3A04A"/></linearGradient></defs><circle cx="60" cy="60" r="50" fill="none" stroke="url(#lg)" stroke-width="7"/><path d="M24 84 Q52 72 60 34 Q68 72 96 84" fill="none" stroke="url(#lg)" stroke-width="5" stroke-linecap="round"/></svg>
  <span class="ar text-gold {{ ($big ?? false) ? 'text-4xl mt-2' : 'text-lg' }}">{{ $s['ar'] }}</span>
  <span class="font-serif text-gold tracking-wide {{ ($big ?? false) ? 'text-4xl' : 'text-xl' }}">{{ $s['name'] }}</span>
</a>
