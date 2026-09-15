<div
    class="relative flex h-screen w-screen flex-col overflow-hidden select-none bg-zinc-950 text-zinc-100"
    x-data="{
        handleKey(e) {
            if (e.key === 'ArrowLeft') {
                if ('{{ $readingDirection }}' === 'rtl') {
                    $wire.nextPage();
                } else {
                    $wire.previousPage();
                }
            } else if (e.key === 'ArrowRight') {
                if ('{{ $readingDirection }}' === 'rtl') {
                    $wire.previousPage();
                } else {
                    $wire.nextPage();
                }
            }
        }
    }"
    x-on:keydown.window="handleKey($event)"
>
    <!-- TOP NAVIGATION BAR -->
    <header class="relative z-30 flex h-14 shrink-0 items-center justify-between border-b border-zinc-800/80 bg-zinc-900/90 px-4 backdrop-blur-md">
        <div class="flex items-center gap-3">
            <a
                href="{{ route('comic-studio') }}"
                class="flex h-9 w-9 items-center justify-center rounded-lg border border-zinc-800 bg-zinc-800/50 text-zinc-400 transition hover:bg-zinc-700 hover:text-white"
                title="{{ __('Back to Comic Studio') }}"
            >
                <flux:icon name="arrow-left" class="h-4 w-4" />
            </a>

            <div class="leading-tight">
                <div class="flex items-center gap-2">
                    <span class="text-xs font-semibold uppercase tracking-wider text-violet-400">{{ $story?->contentTitle?->title ?: ($story?->title ?: __('Comic Series')) }}</span>
                    <span class="rounded bg-zinc-800 px-1.5 py-0.2 text-[10px] font-bold text-zinc-400">EP {{ $episode->episode_number }}</span>
                </div>
                <h1 class="text-sm font-bold text-zinc-100 line-clamp-1">{{ $episode->title }}</h1>
            </div>
        </div>

        <!-- Controls: View Mode, RTL/LTR, Fit, Downloads -->
        <div class="flex items-center gap-2">
            <!-- Spread vs Single Mode -->
            <button
                type="button"
                wire:click="toggleViewMode"
                class="inline-flex items-center gap-1.5 rounded-lg border border-zinc-800 bg-zinc-800/60 px-3 py-1.5 text-xs font-semibold text-zinc-300 transition hover:bg-zinc-700 hover:text-white"
                title="{{ $viewMode === 'spread' ? __('Switch to Single Page') : __('Switch to Dual Spread') }}"
            >
                @if ($viewMode === 'spread')
                    <flux:icon name="book-open" class="h-3.5 w-3.5 text-violet-400" />
                    <span class="hidden sm:inline">{{ __('Dual Spread') }}</span>
                @else
                    <flux:icon name="document" class="h-3.5 w-3.5 text-violet-400" />
                    <span class="hidden sm:inline">{{ __('Single Page') }}</span>
                @endif
            </button>

            <!-- RTL vs LTR Reading Direction -->
            <button
                type="button"
                wire:click="toggleDirection"
                class="inline-flex items-center gap-1.5 rounded-lg border border-zinc-800 bg-zinc-800/60 px-3 py-1.5 text-xs font-semibold transition hover:bg-zinc-700 {{ $readingDirection === 'rtl' ? 'text-rose-400' : 'text-cyan-400' }}"
                title="{{ __('Toggle Reading Flow (RTL Manga vs LTR Western Comic)') }}"
            >
                <span class="font-mono text-[11px] font-bold">{{ strtoupper($readingDirection) }}</span>
                <span class="hidden sm:inline">{{ $readingDirection === 'rtl' ? __('Manga Flow') : __('Comic Flow') }}</span>
            </button>

            <!-- Fit Mode Toggle -->
            <button
                type="button"
                wire:click="toggleFitMode"
                class="inline-flex items-center gap-1.5 rounded-lg border border-zinc-800 bg-zinc-800/60 px-3 py-1.5 text-xs font-semibold text-zinc-300 transition hover:bg-zinc-700 hover:text-white"
            >
                <flux:icon name="arrows-pointing-out" class="h-3.5 w-3.5" />
                <span class="hidden sm:inline">{{ $fitMode === 'fit-height' ? __('Fit Height') : __('Fit Width') }}</span>
            </button>

            <!-- Download Dropdown -->
            <div x-data="{ openDl: false }" class="relative">
                <button
                    type="button"
                    x-on:click="openDl = !openDl"
                    class="inline-flex items-center gap-1 rounded-lg bg-violet-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition hover:bg-violet-500"
                >
                    <flux:icon name="arrow-down-tray" class="h-3.5 w-3.5" />
                    <span class="hidden sm:inline">{{ __('Publishing Packs') }}</span>
                </button>

                <div
                    x-show="openDl"
                    x-on:click.outside="openDl = false"
                    x-transition
                    class="absolute right-0 mt-2 w-56 rounded-xl border border-zinc-800 bg-zinc-900 p-2 shadow-2xl"
                >
                    <button
                        type="button"
                        wire:click="downloadCbz"
                        class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-left text-xs font-medium text-zinc-200 transition hover:bg-violet-600/20 hover:text-violet-300"
                    >
                        <flux:icon name="archive-box" class="h-4 w-4 text-violet-400" />
                        <div>
                            <p class="font-bold">{{ __('Download CBZ Archive') }}</p>
                            <p class="text-[10px] text-zinc-400">{{ __('Standard Comic Book ZIP') }}</p>
                        </div>
                    </button>
                    <button
                        type="button"
                        wire:click="downloadPdf"
                        class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-left text-xs font-medium text-zinc-200 transition hover:bg-violet-600/20 hover:text-violet-300"
                    >
                        <flux:icon name="document-arrow-down" class="h-4 w-4 text-rose-400" />
                        <div>
                            <p class="font-bold">{{ __('Download Print PDF') }}</p>
                            <p class="text-[10px] text-zinc-400">{{ __('High-Res Document') }}</p>
                        </div>
                    </button>
                    <button
                        type="button"
                        wire:click="regeneratePages"
                        class="mt-1 flex w-full items-center gap-2.5 border-t border-zinc-800 px-3 pt-2 text-left text-xs text-zinc-400 transition hover:text-zinc-200"
                    >
                        <flux:icon name="arrow-path" class="h-3.5 w-3.5" />
                        <span>{{ __('Re-render Pages') }}</span>
                    </button>
                </div>
            </div>

            <!-- Thumbnail Drawer Toggle -->
            <button
                type="button"
                wire:click="$toggle('showDrawer')"
                class="flex h-9 w-9 items-center justify-center rounded-lg border border-zinc-800 bg-zinc-800/60 text-zinc-300 transition hover:bg-zinc-700 hover:text-white"
                title="{{ __('Page Thumbnails') }}"
            >
                <flux:icon name="squares-2x2" class="h-4 w-4" />
            </button>
        </div>
    </header>

    <!-- MAIN READING STAGE -->
    <main class="relative flex flex-1 items-center justify-center overflow-hidden bg-zinc-950 p-2 sm:p-4">
        <!-- Click Zone Navigation: Left Side -->
        <div
            wire:click="{{ $readingDirection === 'rtl' ? 'nextPage' : 'previousPage' }}"
            class="group absolute left-0 top-0 z-20 flex h-full w-16 cursor-pointer items-center justify-start pl-2 transition sm:w-24 hover:bg-gradient-to-r hover:from-black/40 hover:to-transparent"
            title="{{ $readingDirection === 'rtl' ? __('Next Page (RTL)') : __('Previous Page') }}"
        >
            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-zinc-900/80 text-zinc-400 shadow-lg opacity-0 transition-opacity group-hover:opacity-100 hover:text-white">
                <flux:icon name="chevron-left" class="h-5 w-5" />
            </div>
        </div>

        <!-- Click Zone Navigation: Right Side -->
        <div
            wire:click="{{ $readingDirection === 'rtl' ? 'previousPage' : 'nextPage' }}"
            class="group absolute right-0 top-0 z-20 flex h-full w-16 cursor-pointer items-center justify-end pr-2 transition sm:w-24 hover:bg-gradient-to-l hover:from-black/40 hover:to-transparent"
            title="{{ $readingDirection === 'rtl' ? __('Previous Page (RTL)') : __('Next Page') }}"
        >
            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-zinc-900/80 text-zinc-400 shadow-lg opacity-0 transition-opacity group-hover:opacity-100 hover:text-white">
                <flux:icon name="chevron-right" class="h-5 w-5" />
            </div>
        </div>

        @if ($totalPages > 0)
            <div class="relative flex max-h-full items-center justify-center drop-shadow-2xl {{ $fitMode === 'fit-height' ? 'h-full' : 'w-full max-w-6xl' }}">
                <!-- DUAL-PAGE SPREAD DISPLAY -->
                @if ($leftPage && $rightPage && $viewMode === 'spread')
                    <div class="relative flex items-center justify-center overflow-hidden rounded-lg bg-zinc-900 {{ $fitMode === 'fit-height' ? 'h-full' : 'w-full' }}">
                        <!-- Left Facing Page -->
                        <div class="relative flex items-center justify-center {{ $fitMode === 'fit-height' ? 'h-full' : 'w-1/2' }}">
                            <img
                                src="{{ Storage::disk('public')->url($leftPage['image_path']) }}"
                                alt="Page {{ $leftPage['page_number'] }}"
                                class="object-contain select-none {{ $fitMode === 'fit-height' ? 'max-h-full w-auto' : 'w-full h-auto' }}"
                            />
                        </div>

                        <!-- Central Book Spine Crease & Shadow -->
                        <div class="pointer-events-none absolute inset-y-0 left-1/2 w-8 -translate-x-1/2 bg-gradient-to-r from-black/50 via-black/10 to-black/50 opacity-60"></div>

                        <!-- Right Facing Page -->
                        <div class="relative flex items-center justify-center {{ $fitMode === 'fit-height' ? 'h-full' : 'w-1/2' }}">
                            <img
                                src="{{ Storage::disk('public')->url($rightPage['image_path']) }}"
                                alt="Page {{ $rightPage['page_number'] }}"
                                class="object-contain select-none {{ $fitMode === 'fit-height' ? 'max-h-full w-auto' : 'w-full h-auto' }}"
                            />
                        </div>
                    </div>
                @else
                    <!-- SINGLE PAGE DISPLAY (Cover or Single Mode) -->
                    @php
                        $single = $rightPage ?: $leftPage;
                    @endphp
                    @if ($single)
                        <div class="relative flex items-center justify-center overflow-hidden rounded-lg bg-zinc-900 shadow-2xl {{ $fitMode === 'fit-height' ? 'h-full' : 'w-full max-w-2xl' }}">
                            <img
                                src="{{ Storage::disk('public')->url($single['image_path']) }}"
                                alt="Page {{ $single['page_number'] }}"
                                class="object-contain select-none {{ $fitMode === 'fit-height' ? 'max-h-full w-auto' : 'w-full h-auto' }}"
                            />
                        </div>
                    @endif
                @endif
            </div>
        @else
            <div class="rounded-2xl border border-dashed border-zinc-800 p-12 text-center">
                <flux:icon name="book-open" class="mx-auto h-12 w-12 text-zinc-600" />
                <h3 class="mt-4 text-base font-bold text-zinc-200">{{ __('Manga Pages Not Yet Built') }}</h3>
                <p class="mt-1 text-xs text-zinc-500">{{ __('Scene boards exist, but composite manga pages have not been rendered yet.') }}</p>
                <div class="mt-6">
                    <button
                        type="button"
                        wire:click="regeneratePages"
                        class="inline-flex items-center gap-2 rounded-xl bg-violet-600 px-4 py-2 text-xs font-semibold text-white hover:bg-violet-500"
                    >
                        <flux:icon name="squares-2x2" class="h-4 w-4" />
                        <span>{{ __('Build Manga Pages Now') }}</span>
                    </button>
                </div>
            </div>
        @endif
    </main>

    <!-- BOTTOM CONTROL & SCRUBBER BAR -->
    <footer class="relative z-30 flex h-14 shrink-0 items-center justify-between border-t border-zinc-800/80 bg-zinc-900/90 px-4 backdrop-blur-md">
        <!-- Episode Switcher: Previous Episode -->
        <div>
            @if ($prevEpisode)
                <a
                    href="{{ route('manga-reader', $prevEpisode->id) }}"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-zinc-800 bg-zinc-800/50 px-3 py-1.5 text-xs font-semibold text-zinc-300 transition hover:bg-zinc-700 hover:text-white"
                >
                    <flux:icon name="arrow-left" class="h-3.5 w-3.5" />
                    <span>EP {{ $prevEpisode->episode_number }}</span>
                </a>
            @else
                <span class="text-xs text-zinc-600 italic">{{ __('Start of Series') }}</span>
            @endif
        </div>

        <!-- Page Scrubber & Counter -->
        <div class="flex items-center gap-3">
            <button
                type="button"
                wire:click="previousPage"
                class="rounded-lg p-1 text-zinc-400 transition hover:bg-zinc-800 hover:text-white"
                title="{{ __('Previous') }}"
            >
                <flux:icon name="chevron-left" class="h-5 w-5" />
            </button>

            <span class="font-mono text-xs font-bold text-zinc-300">
                @if ($viewMode === 'spread' && $leftPage && $rightPage)
                    {{ min($leftPage['page_number'], $rightPage['page_number']) }} - {{ max($leftPage['page_number'], $rightPage['page_number']) }} / {{ $totalPages }}
                @else
                    {{ $currentPage }} / {{ $totalPages }}
                @endif
            </span>

            <button
                type="button"
                wire:click="nextPage"
                class="rounded-lg p-1 text-zinc-400 transition hover:bg-zinc-800 hover:text-white"
                title="{{ __('Next') }}"
            >
                <flux:icon name="chevron-right" class="h-5 w-5" />
            </button>
        </div>

        <!-- Episode Switcher: Next Episode -->
        <div>
            @if ($nextEpisode)
                <a
                    href="{{ route('manga-reader', $nextEpisode->id) }}"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-zinc-800 bg-zinc-800/50 px-3 py-1.5 text-xs font-semibold text-zinc-300 transition hover:bg-zinc-700 hover:text-white"
                >
                    <span>EP {{ $nextEpisode->episode_number }}</span>
                    <flux:icon name="arrow-right" class="h-3.5 w-3.5" />
                </a>
            @else
                <span class="text-xs text-zinc-600 italic">{{ __('Latest Episode') }}</span>
            @endif
        </div>
    </footer>

    <!-- THUMBNAIL DRAWER OVERLAY -->
    @if ($showDrawer)
        <div class="fixed inset-0 z-50 flex flex-col justify-end bg-black/70 backdrop-blur-sm" x-on:keydown.escape.window="$wire.set('showDrawer', false)">
            <div class="relative max-h-80 w-full overflow-x-auto border-t border-zinc-800 bg-zinc-900 p-4 shadow-2xl">
                <div class="flex items-center justify-between pb-3">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-400">{{ __('Episode Pages Quick Jump') }} ({{ $totalPages }} {{ __('pages') }})</h3>
                    <button
                        type="button"
                        wire:click="$set('showDrawer', false)"
                        class="rounded-lg p-1 text-zinc-400 hover:bg-zinc-800 hover:text-white"
                    >
                        <flux:icon name="x-mark" class="h-4 w-4" />
                    </button>
                </div>

                <div class="flex items-center gap-3 overflow-x-auto pb-2">
                    @foreach ($mangaPages as $p)
                        <button
                            type="button"
                            wire:click="setPage({{ $p['page_number'] }})"
                            class="group relative shrink-0 overflow-hidden rounded-lg border transition hover:border-violet-500 {{ $currentPage === $p['page_number'] ? 'border-violet-500 ring-2 ring-violet-500/50' : 'border-zinc-800 bg-zinc-950' }}"
                        >
                            <img
                                src="{{ Storage::disk('public')->url($p['image_path']) }}"
                                alt="Thumbnail {{ $p['page_number'] }}"
                                class="h-32 w-auto object-contain select-none"
                            />
                            <div class="absolute bottom-0 inset-x-0 bg-black/70 py-0.5 text-center text-[10px] font-bold text-zinc-300 group-hover:text-violet-300">
                                p. {{ $p['page_number'] }}
                            </div>
                        </button>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
</div>
