@extends('layouts.admin')
@section('content')
<div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-5">
  @foreach($stats as $l => $v)<div class="bg-white rounded-xl p-4"><div class="text-xs text-gray-500">{{ $l }}</div><div class="text-xl font-bold gtext">{{ $v }}</div></div>@endforeach
</div>
<div class="bg-white rounded-2xl p-4 md:p-5 mb-5">
  <div class="flex justify-between items-center mb-4 flex-wrap gap-3"><h3 class="font-semibold">Sales report</h3>
    <div class="flex gap-2 flex-wrap items-center">
      <div class="flex gap-1" id="modes"><button data-m="day" class="px-3 py-1 rounded-full text-xs font-semibold">Daily</button><button data-m="month" class="px-3 py-1 rounded-full text-xs font-semibold">Monthly</button></div>
      <div class="flex gap-1" id="mets"><button data-t="r" class="px-3 py-1 rounded-full text-xs font-semibold">Revenue ৳</button><button data-t="n" class="px-3 py-1 rounded-full text-xs font-semibold">Orders</button><button data-t="u" class="px-3 py-1 rounded-full text-xs font-semibold">Units</button></div>
    </div></div>
  <form method="GET" class="flex gap-2 items-center mb-4 text-sm flex-wrap">From <input type="date" name="from" value="{{ $from }}" class="border rounded px-2 py-1"> To <input type="date" name="to" value="{{ $to }}" class="border rounded px-2 py-1"><button class="btn !py-1 !px-4 text-sm">Show</button><span class="text-gray-500">(daily graph, max 92 days)</span></form>
  <canvas id="c" height="120"></canvas>
</div>
<div class="grid md:grid-cols-2 gap-5">
  <div class="bg-white rounded-2xl p-4"><h3 class="font-semibold mb-2">Top selling (selected range)</h3>
    @forelse($top as $t)<div class="flex justify-between text-sm py-1 border-b last:border-0"><span>{{ $t->name }}</span><b>{{ $t->q }} sold</b></div>@empty<p class="text-sm text-gray-500">No sales in this range.</p>@endforelse</div>
  <div class="bg-white rounded-2xl p-4"><h3 class="font-semibold mb-2">Low / out of stock</h3>
    @forelse($low as $p)<div class="flex justify-between text-sm py-1 border-b last:border-0"><span>{{ $p->name }}</span><b class="{{ $p->stock <= 0 ? 'text-red-600' : 'text-mag' }}">{{ $p->stock }} left</b></div>@empty<p class="text-sm text-gray-500">All products are well stocked.</p>@endforelse</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
const D = {day: @json($daily), month: @json($monthly)};
let mode = 'day', met = 'r', chart;
const names = {r: 'Revenue ৳', n: 'Orders', u: 'Units'};
function paint() {
  document.querySelectorAll('#modes button').forEach(b => b.className = 'px-3 py-1 rounded-full text-xs font-semibold ' + (b.dataset.m === mode ? 'grad text-white' : 'border'));
  document.querySelectorAll('#mets button').forEach(b => b.className = 'px-3 py-1 rounded-full text-xs font-semibold ' + (b.dataset.t === met ? 'grad text-white' : 'border'));
  const a = D[mode];
  if (chart) chart.destroy();
  chart = new Chart(document.getElementById('c'), {type: 'bar', data: {labels: a.map(x => x.l), datasets: [{label: names[met], data: a.map(x => x[met]), backgroundColor: '#B03A9A', borderRadius: 4}]}, options: {plugins: {legend: {display: false}}, scales: {y: {beginAtZero: true, ticks: {precision: 0}}}}});
}
document.querySelectorAll('#modes button').forEach(b => b.onclick = () => { mode = b.dataset.m; paint(); });
document.querySelectorAll('#mets button').forEach(b => b.onclick = () => { met = b.dataset.t; paint(); });
paint();
</script>
@endsection
