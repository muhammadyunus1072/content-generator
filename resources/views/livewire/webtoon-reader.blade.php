<div class="min-h-screen {{ $bgTheme === 'dark' ? 'bg-[#0b0c10] text-zinc-100' : 'bg-[#f4f5f7] text-zinc-900' }} transition-colors duration-300">
    <!-- Sticky Reader Toolbar -->
    <header class="sticky top-0 z-40 border-b {{ $bgTheme === 'dark' ? 'border-zinc-800 bg-[#0b0c10]/90' : 'border-zinc-200 bg-white/90' }} backdrop-blur-md px-4 py-3 shadow-md">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-4">
            <!-- Left: Back & Title -->
            <div class="flex items-center gap-3">
                <a
                    href="{{ route('comic-studio') }}"
                    class="rounded-xl border {{ $bgTheme === 'dark' ? 'border-zinc-800 bg-zinc-900 text-zinc-300 hover:bg-zinc-800' : 'border-zinc-200 bg-zinc-100 text-zinc-700 hover:bg-zinc-200' }} p-2 text-xs font-semibold transition"
                    title="{{ __('Back to Comic Studio') }}"
                >
                    <flux:icon name="arrow-left" class="h-4 w-4" />
                </a>
                <div>
                    <h1 class="text-sm font-bold truncate max-w-xs sm:max-w-md">
                        {{ $story?->title ?: 'Comic Series' }} — EP {{ $episode->episode_number }}: {{ $episode->title }}
                    </h1>
                    <p class="text-[11px] text-zinc-400">
                        {{ count($episode->webtoon_slices ?: []) }} {{ __('slices') }} | Webtoon Canvas & Tapas Ready
                    </p>
                </div>
            </div>

            <!-- Center: Prev / Next Switcher -->
            <div class="hidden sm:flex items-center gap-1 rounded-xl border {{ $bgTheme === 'dark' ? 'border-zinc-800 bg-zinc-900' : 'border-zinc-200 bg-zinc-100' }} p-1 text-xs">
                @if ($prevEpisode)
                    <a href="{{ route('webtoon-reader', $prevEpisode->id) }}" class="rounded-lg px-2.5 py-1 font-semibold hover:text-violet-500 transition">
                        {{ __('← EP') }} {{ $prevEpisode->episode_number }}
                    </a>
                @else
                    <span class="px-2.5 py-1 text-zinc-500 cursor-not-allowed">{{ __('← First') }}</span>
                @endif

                <span class="text-zinc-500">|</span>

                @if ($nextEpisode)
                    <a href="{{ route('webtoon-reader', $nextEpisode->id) }}" class="rounded-lg px-2.5 py-1 font-semibold hover:text-violet-500 transition">
                        {{ __('EP') }} {{ $nextEpisode->episode_number }} {{ __('→') }}
                    </a>
                @else
                    <span class="px-2.5 py-1 text-zinc-500 cursor-not-allowed">{{ __('End →') }}</span>
                @endif
            </div>

            <!-- Right: Reader Controls -->
            <div class="flex items-center gap-2">
                <!-- Width Switcher -->
                <div class="hidden md:flex items-center rounded-lg border {{ $bgTheme === 'dark' ? 'border-zinc-800 bg-zinc-900' : 'border-zinc-200 bg-zinc-100' }} p-0.5 text-[11px]">
                    <button
                        type="button"
                        wire:click="setReaderWidth(600)"
                        class="rounded px-2 py-1 font-semibold transition {{ $readerWidth === 600 ? 'bg-violet-600 text-white' : 'text-zinc-400 hover:text-zinc-200' }}"
                    >
                        600px
                    </button>
                    <button
                        type="button"
                        wire:click="setReaderWidth(800)"
                        class="rounded px-2 py-1 font-semibold transition {{ $readerWidth === 800 ? 'bg-violet-600 text-white' : 'text-zinc-400 hover:text-zinc-200' }}"
                    >
                        800px
                    </button>
                    <button
                        type="button"
                        wire:click="setReaderWidth(1000)"
                        class="rounded px-2 py-1 font-semibold transition {{ $readerWidth === 1000 ? 'bg-violet-600 text-white' : 'text-zinc-400 hover:text-zinc-200' }}"
                    >
                        1000px
                    </button>
                </div>

                <!-- Theme Toggle -->
                <button
                    type="button"
                    wire:click="toggleBgTheme"
                    class="rounded-xl border {{ $bgTheme === 'dark' ? 'border-zinc-800 bg-zinc-900 text-zinc-300' : 'border-zinc-200 bg-zinc-100 text-zinc-700' }} p-2 text-xs transition"
                    title="{{ __('Toggle Dark / Light Background') }}"
                >
                    @if ($bgTheme === 'dark')
                        <flux:icon name="sun" class="h-4 w-4 text-amber-400" />
                    @else
                        <flux:icon name="moon" class="h-4 w-4 text-zinc-700" />
                    @endif
                </button>

                <!-- Export Pack Button -->
                <button
                    type="button"
                    wire:click="downloadPublishingPack"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-violet-600 px-3.5 py-2 text-xs font-bold text-white shadow-sm transition hover:bg-violet-500"
                >
                    <flux:icon name="arrow-down-tray" class="h-4 w-4" />
                    <span class="hidden sm:inline">{{ __('Export Pack (ZIP)') }}</span>
                </button>
            </div>
        </div>
    </header>

    <!-- Main Vertical Scroll Reading Canvas -->
    <main class="py-6 flex flex-col items-center">
        <div
            class="transition-all duration-200 shadow-2xl flex flex-col items-center select-none"
            style="width: min(100%, {{ $readerWidth }}px);"
        >
            @if (! empty($episode->webtoon_slices) && is_array($episode->webtoon_slices))
                <!-- Render Seamless Slices -->
                @foreach ($episode->webtoon_slices as $sliceRel)
                    <img
                        src="{{ Storage::disk('public')->url($sliceRel) }}"
                        alt="Webtoon Panel Slice"
                        class="w-full h-auto block m-0 p-0 border-0 pointer-events-none"
                        loading="lazy"
                    />
                @endforeach
            @else
                <!-- Fallback: Render ready boards directly -->
                @forelse ($episode->boards as $board)
                    @php
                        $displayImg = $board->speech_bubble_image_path ?: $board->image_path;
                    @endphp
                    @if ($displayImg)
                        <img
                            src="{{ Storage::disk('public')->url($displayImg) }}"
                            alt="Comic Panel {{ $board->board_number }}"
                            class="w-full h-auto block m-0 p-0 pointer-events-none"
                            style="margin-bottom: {{ $board->gutter_spacing ?? 80 }}px;"
                        />
                    @endif
                @empty
                    <div class="py-20 text-center text-zinc-500">
                        <flux:icon name="photo" class="mx-auto h-12 w-12 text-zinc-400" />
                        <p class="mt-2 font-semibold">{{ __('No panel images available to read.') }}</p>
                    </div>
                @endforelse
            @endif
        </div>

        <!-- Chapter End Navigation Footer -->
        <footer class="mt-16 w-full max-w-xl text-center px-4 py-8 border-t {{ $bgTheme === 'dark' ? 'border-zinc-800' : 'border-zinc-300' }}">
            <h3 class="text-sm font-bold uppercase tracking-wider text-zinc-400">
                {{ __('End of Episode') }} {{ $episode->episode_number }}
            </h3>
            <p class="mt-1 text-xs text-zinc-500">
                {{ $episode->title }}
            </p>

            <div class="mt-6 flex items-center justify-center gap-4">
                @if ($prevEpisode)
                    <a
                        href="{{ route('webtoon-reader', $prevEpisode->id) }}"
                        class="inline-flex items-center gap-1 rounded-xl border border-zinc-700 px-4 py-2 text-xs font-semibold hover:bg-zinc-800 transition"
                    >
                        {{ __('← Previous Episode') }}
                    </a>
                @endif

                @if ($nextEpisode)
                    <a
                        href="{{ route('webtoon-reader', $nextEpisode->id) }}"
                        class="inline-flex items-center gap-1 rounded-xl bg-violet-600 px-5 py-2.5 text-xs font-bold text-white hover:bg-violet-500 transition shadow-sm"
                    >
                        {{ __('Next Episode →') }}
                    </a>
                @endif
            </div>

            <div class="mt-8 flex justify-center gap-3">
                <button
                    type="button"
                    wire:click="regenerateStrip"
                    class="text-xs text-zinc-400 hover:text-zinc-200 underline"
                >
                    {{ __('Re-stitch & Slice Strip') }}
                </button>
            </div>
        </footer>
    </main>
</div>
