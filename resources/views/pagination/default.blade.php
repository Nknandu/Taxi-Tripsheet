@if ($paginator->hasPages())
<ul class="pagination">
    {{-- Previous Page Link --}}
    @if ($paginator->onFirstPage())
        <li class="page-item previous disabled">
            <span class="page-link page-text">{{ __('messages.previous') }}</span>
        </li>
    @else
        <li class="page-item previous">
            <a class="page-link page-text" href="{{ $paginator->previousPageUrl() }}">{{ __('messages.previous') }}</a>
        </li>
    @endif
    {{-- Pagination Elements --}}
    @foreach ($elements as $element)
        {{-- "Three Dots" Separator --}}
        @if (is_string($element))
            <li class="page-item disabled"><span>{{ $element }}</span></li>
        @endif

        {{-- Array Of Links --}}
        @if (is_array($element))
            @foreach ($element as $page => $url)
                @if ($page == $paginator->currentPage())
                    <li class="page-item active"><a class="page-link" >{{ $page }}</a></li>
                @else
                    <li class="page-item"><a class="page-link" href="{{ $url }}">{{ $page }}</a></li>
                @endif
            @endforeach
        @endif
    @endforeach

    {{-- Next Page Link --}}
    @if ($paginator->hasMorePages())
        <li class="page-item next">
            <a class="page-link page-text" href="{{ $paginator->nextPageUrl() }}">{{ __('messages.next') }}</a>
        </li>
    @else
        <li class="page-item next disabled">
            <span class="page-link page-text">{{ __('messages.next') }}</span>
        </li>
    @endif

</ul>
@endif
