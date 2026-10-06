{{-- Pagination view for the back office: $paginator->links('admin.dons.partials.pagination') --}}
@if ($paginator->hasPages())
  <nav class="d-flex flex-wrap align-center justify-space-between gap-4" role="navigation" aria-label="Pagination">
    <p class="mb-0 text-body-2 text-medium-emphasis">
      {{ $paginator->firstItem() }} à {{ $paginator->lastItem() }} sur {{ $paginator->total() }} résultats
    </p>
    <ul class="tcd-pagination">
      @if ($paginator->onFirstPage())
        <li class="disabled" aria-disabled="true"><span>‹</span></li>
      @else
        <li><a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Page précédente">‹</a></li>
      @endif

      @foreach ($elements as $element)
        @if (is_string($element))
          <li class="disabled" aria-disabled="true"><span>{{ $element }}</span></li>
        @endif
        @if (is_array($element))
          @foreach ($element as $page => $url)
            @if ($page == $paginator->currentPage())
              <li class="active" aria-current="page"><span>{{ $page }}</span></li>
            @else
              <li><a href="{{ $url }}">{{ $page }}</a></li>
            @endif
          @endforeach
        @endif
      @endforeach

      @if ($paginator->hasMorePages())
        <li><a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Page suivante">›</a></li>
      @else
        <li class="disabled" aria-disabled="true"><span>›</span></li>
      @endif
    </ul>
  </nav>
@endif
