{{-- Pagination au style Vuetify (utilisée via $paginator->links('admin.upcycling.partials.pagination')) --}}
@if ($paginator->hasPages())
  @php($bouton = 'v-btn v-btn--icon v-theme--light v-btn--density-comfortable v-btn--size-default v-btn--variant-text')
  <nav class="v-pagination" role="navigation" aria-label="Pagination">
    <ul class="v-pagination__list">
      <li class="v-pagination__prev">
        @if ($paginator->onFirstPage())
          <span class="{{ $bouton }} v-btn--disabled" aria-disabled="true"><span class="v-btn__overlay"></span><span class="v-btn__underlay"></span><span class="v-btn__content"><i class="bx-chevron-left v-icon notranslate v-theme--light" aria-hidden="true"></i></span></span>
        @else
          <a href="{{ $paginator->previousPageUrl() }}" class="{{ $bouton }}" rel="prev" aria-label="Page précédente"><span class="v-btn__overlay"></span><span class="v-btn__underlay"></span><span class="v-btn__content"><i class="bx-chevron-left v-icon notranslate v-theme--light" aria-hidden="true"></i></span></a>
        @endif
      </li>

      @foreach ($elements as $element)
        @if (is_string($element))
          <li class="v-pagination__item"><span class="{{ $bouton }} v-btn--disabled"><span class="v-btn__content">{{ $element }}</span></span></li>
        @endif

        @if (is_array($element))
          @foreach ($element as $page => $url)
            <li @class(['v-pagination__item', 'v-pagination__item--is-active' => $page == $paginator->currentPage()])>
              @if ($page == $paginator->currentPage())
                <span class="{{ $bouton }} bg-primary v-btn--variant-elevated" aria-current="page"><span class="v-btn__overlay"></span><span class="v-btn__underlay"></span><span class="v-btn__content">{{ $page }}</span></span>
              @else
                <a href="{{ $url }}" class="{{ $bouton }}"><span class="v-btn__overlay"></span><span class="v-btn__underlay"></span><span class="v-btn__content">{{ $page }}</span></a>
              @endif
            </li>
          @endforeach
        @endif
      @endforeach

      <li class="v-pagination__next">
        @if ($paginator->hasMorePages())
          <a href="{{ $paginator->nextPageUrl() }}" class="{{ $bouton }}" rel="next" aria-label="Page suivante"><span class="v-btn__overlay"></span><span class="v-btn__underlay"></span><span class="v-btn__content"><i class="bx-chevron-right v-icon notranslate v-theme--light" aria-hidden="true"></i></span></a>
        @else
          <span class="{{ $bouton }} v-btn--disabled" aria-disabled="true"><span class="v-btn__overlay"></span><span class="v-btn__underlay"></span><span class="v-btn__content"><i class="bx-chevron-right v-icon notranslate v-theme--light" aria-hidden="true"></i></span></span>
        @endif
      </li>
    </ul>
  </nav>
@endif
