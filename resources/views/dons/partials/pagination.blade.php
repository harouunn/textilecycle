{{-- Pagination du site (Bootstrap 5), en français quelle que soit la langue de l'application : $liste->links('dons.partials.pagination') --}}
@if ($paginator->hasPages())
  <nav class="d-flex flex-column align-items-center gap-2" aria-label="Pagination">
    <ul class="pagination mb-0">
      @if ($paginator->onFirstPage())
        <li class="page-item disabled" aria-disabled="true"><span class="page-link" aria-hidden="true">‹</span></li>
      @else
        <li class="page-item"><a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Page précédente">‹</a></li>
      @endif

      @foreach ($elements as $element)
        @if (is_string($element))
          <li class="page-item disabled" aria-disabled="true"><span class="page-link">{{ $element }}</span></li>
        @endif
        @if (is_array($element))
          @foreach ($element as $page => $url)
            @if ($page == $paginator->currentPage())
              <li class="page-item active" aria-current="page"><span class="page-link">{{ $page }}</span></li>
            @else
              <li class="page-item"><a class="page-link" href="{{ $url }}" aria-label="Page {{ $page }}">{{ $page }}</a></li>
            @endif
          @endforeach
        @endif
      @endforeach

      @if ($paginator->hasMorePages())
        <li class="page-item"><a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Page suivante">›</a></li>
      @else
        <li class="page-item disabled" aria-disabled="true"><span class="page-link" aria-hidden="true">›</span></li>
      @endif
    </ul>
    <p class="small text-muted mb-0">{{ $paginator->firstItem() }} à {{ $paginator->lastItem() }} sur {{ $paginator->total() }} résultats</p>
  </nav>
@endif
