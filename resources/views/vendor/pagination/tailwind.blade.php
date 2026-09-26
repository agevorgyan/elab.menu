@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; margin-top: 1.25rem; font-family: 'Inter', sans-serif;">
        
        <!-- Results count info -->
        <div style="font-size: 0.82rem; color: var(--text-muted);">
            {!! __('Showing') !!}
            @if ($paginator->firstItem())
                <strong style="color: var(--text-main);">{{ $paginator->firstItem() }}</strong>
                {!! __('to') !!}
                <strong style="color: var(--text-main);">{{ $paginator->lastItem() }}</strong>
            @else
                {{ $paginator->count() }}
            @endif
            {!! __('of') !!}
            <strong style="color: var(--text-main);">{{ $paginator->total() }}</strong>
            {!! __('results') !!}
        </div>

        <!-- Pagination Buttons -->
        <div style="display: inline-flex; align-items: center; gap: 0.35rem; flex-wrap: wrap;">
            
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <span style="display: inline-flex; align-items: center; justify-content: center; width: 34px; height: 34px; border-radius: 8px; border: 1px solid var(--border-color); color: var(--text-muted); opacity: 0.45; cursor: not-allowed; background: var(--bg-card);" aria-disabled="true" aria-label="{{ __('pagination.previous') }}">
                    <i class="fa-solid fa-chevron-left" style="font-size: 0.75rem;"></i>
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" style="display: inline-flex; align-items: center; justify-content: center; width: 34px; height: 34px; border-radius: 8px; border: 1px solid var(--border-color); color: var(--text-main); text-decoration: none; background: var(--bg-card); transition: all 0.2s ease;" aria-label="{{ __('pagination.previous') }}" onmouseover="this.style.borderColor='var(--primary)'" onmouseout="this.style.borderColor='var(--border-color)'">
                    <i class="fa-solid fa-chevron-left" style="font-size: 0.75rem;"></i>
                </a>
            @endif

            {{-- Pagination Elements --}}
            @foreach ($elements as $element)
                {{-- "Three Dots" Separator --}}
                @if (is_string($element))
                    <span style="display: inline-flex; align-items: center; justify-content: center; width: 34px; height: 34px; color: var(--text-muted); font-size: 0.85rem;" aria-disabled="true">
                        {{ $element }}
                    </span>
                @endif

                {{-- Array Of Links --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span style="display: inline-flex; align-items: center; justify-content: center; min-width: 34px; height: 34px; padding: 0 0.5rem; border-radius: 8px; background: var(--primary); color: #ffffff; font-weight: 700; font-size: 0.85rem; box-shadow: 0 2px 8px var(--primary-glow);" aria-current="page">
                                {{ $page }}
                            </span>
                        @else
                            <a href="{{ $url }}" style="display: inline-flex; align-items: center; justify-content: center; min-width: 34px; height: 34px; padding: 0 0.5rem; border-radius: 8px; border: 1px solid var(--border-color); color: var(--text-main); text-decoration: none; font-size: 0.85rem; background: var(--bg-card); transition: all 0.2s ease;" onmouseover="this.style.borderColor='var(--primary)'" onmouseout="this.style.borderColor='var(--border-color)'">
                                {{ $page }}
                            </a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" style="display: inline-flex; align-items: center; justify-content: center; width: 34px; height: 34px; border-radius: 8px; border: 1px solid var(--border-color); color: var(--text-main); text-decoration: none; background: var(--bg-card); transition: all 0.2s ease;" aria-label="{{ __('pagination.next') }}" onmouseover="this.style.borderColor='var(--primary)'" onmouseout="this.style.borderColor='var(--border-color)'">
                    <i class="fa-solid fa-chevron-right" style="font-size: 0.75rem;"></i>
                </a>
            @else
                <span style="display: inline-flex; align-items: center; justify-content: center; width: 34px; height: 34px; border-radius: 8px; border: 1px solid var(--border-color); color: var(--text-muted); opacity: 0.45; cursor: not-allowed; background: var(--bg-card);" aria-disabled="true" aria-label="{{ __('pagination.next') }}">
                    <i class="fa-solid fa-chevron-right" style="font-size: 0.75rem;"></i>
                </span>
            @endif

        </div>
    </nav>
@endif
