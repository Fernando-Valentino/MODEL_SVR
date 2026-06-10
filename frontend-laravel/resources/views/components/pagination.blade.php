@if ($paginator->hasPages())
    <ul class="pagination" role="navigation">
        {{-- Previous Page Link --}}
        @if ($paginator->onFirstPage())
            <li class="page-item disabled" aria-disabled="true">
                <span class="page-link"><i class="bi bi-chevron-left"></i></span>
            </li>
        @else
            <li class="page-item">
                <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev"><i class="bi bi-chevron-left"></i></a>
            </li>
        @endif

        {{-- Pagination Elements --}}
        @php
            $currentPage = $paginator->currentPage();
            $lastPage = $paginator->lastPage();
            $range = 1; // How many pages on each side of the active page to show
        @endphp

        @if ($lastPage <= 7)
            @for ($i = 1; $i <= $lastPage; $i++)
                @if ($i == $currentPage)
                    <li class="page-item active" aria-current="page"><span class="page-link">{{ $i }}</span></li>
                @else
                    <li class="page-item"><a class="page-link" href="{{ $paginator->url($i) }}">{{ $i }}</a></li>
                @endif
            @endfor
        @else
            {{-- Always show page 1 --}}
            @if ($currentPage == 1)
                <li class="page-item active" aria-current="page"><span class="page-link">1</span></li>
            @else
                <li class="page-item"><a class="page-link" href="{{ $paginator->url(1) }}">1</a></li>
            @endif

            {{-- Left Ellipsis --}}
            @if ($currentPage > 3)
                <li class="page-item disabled" aria-disabled="true"><span class="page-link">...</span></li>
            @endif

            {{-- Middle Pages --}}
            @php
                $start = max(2, $currentPage - $range);
                $end = min($lastPage - 1, $currentPage + $range);
                
                // Adjust ranges to keep total items consistent
                if ($currentPage <= 3) {
                    $end = 4;
                }
                if ($currentPage >= $lastPage - 2) {
                    $start = $lastPage - 3;
                }
            @endphp

            @for ($i = $start; $i <= $end; $i++)
                @if ($i == $currentPage)
                    <li class="page-item active" aria-current="page"><span class="page-link">{{ $i }}</span></li>
                @else
                    <li class="page-item"><a class="page-link" href="{{ $paginator->url($i) }}">{{ $i }}</a></li>
                @endif
            @endfor

            {{-- Right Ellipsis --}}
            @if ($currentPage < $lastPage - 2)
                <li class="page-item disabled" aria-disabled="true"><span class="page-link">...</span></li>
            @endif

            {{-- Always show last page --}}
            @if ($currentPage == $lastPage)
                <li class="page-item active" aria-current="page"><span class="page-link">{{ $lastPage }}</span></li>
            @else
                <li class="page-item"><a class="page-link" href="{{ $paginator->url($lastPage) }}">{{ $lastPage }}</a></li>
            @endif
        @endif

        {{-- Next Page Link --}}
        @if ($paginator->hasMorePages())
            <li class="page-item">
                <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next"><i class="bi bi-chevron-right"></i></a>
            </li>
        @else
            <li class="page-item disabled" aria-disabled="true">
                <span class="page-link"><i class="bi bi-chevron-right"></i></span>
            </li>
        @endif
    </ul>
@endif
