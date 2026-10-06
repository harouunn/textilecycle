{{--
  Horizontal bars, one hue (magnitude). Each row shows its label and value as text.
  Paramètres : barRows (collection de ['label', 'value' (nombre), 'text', 'hint' optionnel, 'href' optionnel]), barEmpty
--}}
@php($barMax = max(1, (float) collect($barRows)->max('value')))
@forelse ($barRows as $barRow)
  <{!! isset($barRow['href']) ? 'a href="'.e($barRow['href']).'"' : 'div' !!} class="tcs-hbar text-high-emphasis" style="text-decoration: none;"
    title="{{ $barRow['label'] }} : {{ $barRow['text'] }}{{ isset($barRow['hint']) ? ' · '.$barRow['hint'] : '' }}">
    <span class="tcs-hbar-label">{{ $barRow['label'] }}</span>
    <span class="tcs-track"><span class="tcs-fill" style="width: {{ (float) $barRow['value'] * 100 / $barMax }}%"></span></span>
    <span class="tcs-value">{{ $barRow['text'] }}@isset($barRow['hint']) <small>· {{ $barRow['hint'] }}</small>@endisset</span>
  </{!! isset($barRow['href']) ? 'a' : 'div' !!}>
@empty
  <p class="mb-0 text-medium-emphasis">{{ $barEmpty ?? 'Aucune donnée pour le moment.' }}</p>
@endforelse
