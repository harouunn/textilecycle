{{--
  Stacked 100 % bar + legend (state distribution).
  Paramètres : stackRows (collection de ['label', 'color', 'total', 'href' optionnel]), stackAria
--}}
@php($stackTotal = max(1, collect($stackRows)->sum('total')))
@if (collect($stackRows)->sum('total') > 0)
  <div class="tcs-stack" role="img" aria-label="{{ $stackAria }}">
    @foreach (collect($stackRows)->where('total', '>', 0) as $stackRow)
      <span class="bg-{{ $stackRow['color'] }}" style="flex: {{ $stackRow['total'] }}" title="{{ $stackRow['label'] }} : {{ $stackRow['total'] }} ({{ round($stackRow['total'] * 100 / $stackTotal) }} %)"></span>
    @endforeach
  </div>
@endif
<div class="tcs-legend">
  @foreach ($stackRows as $stackRow)
    <{!! isset($stackRow['href']) ? 'a href="'.e($stackRow['href']).'"' : 'div' !!} class="text-high-emphasis" style="text-decoration: none;">
      <div class="text-body-2"><span class="tcs-swatch bg-{{ $stackRow['color'] }}"></span>{{ $stackRow['label'] }}</div>
      <div class="text-h6">{{ $stackRow['total'] }} <small class="text-body-2 text-medium-emphasis">· {{ round($stackRow['total'] * 100 / $stackTotal) }} %</small></div>
    </{!! isset($stackRow['href']) ? 'a' : 'div' !!}>
  @endforeach
</div>
