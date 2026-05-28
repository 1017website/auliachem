@if ($paginator->hasPages())
<nav class="app-pagination" role="navigation" aria-label="Pagination">
    <ul class="app-pagination-list">
        {{-- Previous --}}
        @if ($paginator->onFirstPage())
            <li class="app-page disabled" aria-disabled="true"><span><i class="fas fa-chevron-left"></i></span></li>
        @else
            <li class="app-page"><a href="{{ $paginator->previousPageUrl() }}" rel="prev"><i class="fas fa-chevron-left"></i></a></li>
        @endif

        {{-- Page numbers --}}
        @foreach ($elements as $element)
            @if (is_string($element))
                <li class="app-page dots"><span>{{ $element }}</span></li>
            @endif
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <li class="app-page active" aria-current="page"><span>{{ $page }}</span></li>
                    @else
                        <li class="app-page"><a href="{{ $url }}">{{ $page }}</a></li>
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- Next --}}
        @if ($paginator->hasMorePages())
            <li class="app-page"><a href="{{ $paginator->nextPageUrl() }}" rel="next"><i class="fas fa-chevron-right"></i></a></li>
        @else
            <li class="app-page disabled" aria-disabled="true"><span><i class="fas fa-chevron-right"></i></span></li>
        @endif
    </ul>
</nav>

<style>
    .app-pagination { display: flex; }
    .app-pagination-list {
        display: flex; align-items: center; gap: 4px;
        list-style: none; margin: 0; padding: 0;
    }
    .app-page > a,
    .app-page > span {
        display: inline-flex; align-items: center; justify-content: center;
        min-width: 32px; height: 32px; padding: 0 8px;
        font-size: .8rem; font-weight: 500;
        color: #374151; background: #fff;
        border: 1px solid #e5e7eb; border-radius: 8px;
        text-decoration: none; transition: all .15s ease;
    }
    .app-page > a:hover {
        background: #eff6ff; border-color: #bfdbfe; color: #2563eb;
    }
    .app-page.active > span {
        background: #2563eb; border-color: #2563eb; color: #fff;
        box-shadow: 0 2px 6px rgba(37,99,235,.25);
    }
    .app-page.disabled > span {
        color: #d1d5db; background: #f9fafb; cursor: not-allowed;
    }
    .app-page.dots > span {
        border-color: transparent; background: transparent; color: #9ca3af;
    }
</style>
@endif
