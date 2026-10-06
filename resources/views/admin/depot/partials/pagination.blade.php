{{-- Pagination au style Sneat : $vetements->links('admin.depot.partials.pagination') --}}
@if ($paginator->hasPages())
  <nav class="d-flex flex-wrap align-center justify-space-between gap-4" aria-label="Pagination">
    <span class="text-body-2 text-disabled">
      {{ $paginator->firstItem() }} à {{ $paginator->lastItem() }} sur {{ $paginator->total() }} résultats
    </span>
    <div class="tc-pagination">
      @if ($paginator->onFirstPage())
        <span class="v-btn v-btn--disabled v-btn--icon v-theme--light v-btn--density-comfortable v-btn--size-small v-btn--variant-tonal"><span class="v-btn__content"><i class="bx-chevron-left v-icon notranslate v-theme--light" aria-hidden="true"></i></span></span>
      @else
        <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Page précédente" class="v-btn v-btn--icon v-theme--light text-primary v-btn--density-comfortable v-btn--size-small v-btn--variant-tonal"><span class="v-btn__overlay"></span><span class="v-btn__underlay"></span><span class="v-btn__content"><i class="bx-chevron-left v-icon notranslate v-theme--light" aria-hidden="true"></i></span></a>
      @endif

      @foreach ($elements as $element)
        @if (is_string($element))
          <span class="v-btn v-btn--icon v-theme--light v-btn--density-comfortable v-btn--size-small v-btn--variant-text"><span class="v-btn__content">{{ $element }}</span></span>
        @else
          @foreach ($element as $page => $url)
            <a href="{{ $url }}" @if ($page == $paginator->currentPage()) aria-current="page" @endif
              class="v-btn v-btn--icon v-theme--light v-btn--density-comfortable v-btn--size-small {{ $page == $paginator->currentPage() ? 'v-btn--variant-elevated v-btn--elevated bg-primary' : 'v-btn--variant-tonal text-primary' }}"><span class="v-btn__overlay"></span><span class="v-btn__underlay"></span><span class="v-btn__content">{{ $page }}</span></a>
          @endforeach
        @endif
      @endforeach

      @if ($paginator->hasMorePages())
        <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Page suivante" class="v-btn v-btn--icon v-theme--light text-primary v-btn--density-comfortable v-btn--size-small v-btn--variant-tonal"><span class="v-btn__overlay"></span><span class="v-btn__underlay"></span><span class="v-btn__content"><i class="bx-chevron-right v-icon notranslate v-theme--light" aria-hidden="true"></i></span></a>
      @else
        <span class="v-btn v-btn--disabled v-btn--icon v-theme--light v-btn--density-comfortable v-btn--size-small v-btn--variant-tonal"><span class="v-btn__content"><i class="bx-chevron-right v-icon notranslate v-theme--light" aria-hidden="true"></i></span></span>
      @endif
    </div>
  </nav>
@endif
