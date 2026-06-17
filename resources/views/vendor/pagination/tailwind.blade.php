@if ($paginator->hasPages())
<nav style="display:flex;align-items:center;gap:6px;flex-wrap:wrap">

    {{-- Previous --}}
    @if ($paginator->onFirstPage())
        <span class="btn btn-sm btn-ghost" style="opacity:.4;cursor:default;pointer-events:none">
            <svg width="12" height="12" viewBox="0 0 16 16" fill="currentColor"><path d="M10 3L5 8l5 5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" fill="none"/></svg>
            Trước
        </span>
    @else
        <a href="{{ $paginator->previousPageUrl() }}" class="btn btn-sm btn-ghost">
            <svg width="12" height="12" viewBox="0 0 16 16" fill="currentColor"><path d="M10 3L5 8l5 5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" fill="none"/></svg>
            Trước
        </a>
    @endif

    {{-- Page numbers --}}
    @foreach ($elements as $element)
        @if (is_string($element))
            <span style="color:var(--text-3);padding:0 2px">…</span>
        @endif

        @if (is_array($element))
            @foreach ($element as $page => $url)
                @if ($page == $paginator->currentPage())
                    <span class="btn btn-sm btn-primary" style="min-width:32px;justify-content:center">{{ $page }}</span>
                @else
                    <a href="{{ $url }}" class="btn btn-sm btn-ghost" style="min-width:32px;justify-content:center">{{ $page }}</a>
                @endif
            @endforeach
        @endif
    @endforeach

    {{-- Next --}}
    @if ($paginator->hasMorePages())
        <a href="{{ $paginator->nextPageUrl() }}" class="btn btn-sm btn-ghost">
            Sau
            <svg width="12" height="12" viewBox="0 0 16 16" fill="currentColor"><path d="M6 3l5 5-5 5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" fill="none"/></svg>
        </a>
    @else
        <span class="btn btn-sm btn-ghost" style="opacity:.4;cursor:default;pointer-events:none">
            Sau
            <svg width="12" height="12" viewBox="0 0 16 16" fill="currentColor"><path d="M6 3l5 5-5 5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" fill="none"/></svg>
        </span>
    @endif

    <span style="margin-left:8px;color:var(--text-3);font-size:12px">
        {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} / {{ $paginator->total() }}
    </span>

</nav>
@endif
