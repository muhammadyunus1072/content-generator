<div 
    class="w-full space-y-6"
    @if (in_array($story->status, ['pending', 'decomposing', 'assets_generating', 'rendering']))
        wire:poll.5s
    @endif
>
    <!-- Top Bar Navigation & Actions -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-3">
            <a 
                href="{{ route('story-studio') }}" 
                class="inline-flex items-center gap-2 text-xs font-semibold text-zinc-500 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100"
                wire:navigate
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                {{ __('Back to Story Studio') }}
            </a>
            <span class="text-zinc-300 dark:text-zinc-700">•</span>
            <span class="rounded-full px-2.5 py-0.5 text-xs font-bold {{ $story->status === 'completed' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-400' : ($story->status === 'rendering' || $story->status === 'assets_generating' ? 'bg-amber-100 text-amber-800 animate-pulse dark:bg-amber-950/50 dark:text-amber-400' : 'bg-blue-100 text-blue-800 dark:bg-blue-950/50 dark:text-blue-400') }}">
                ● {{ ucfirst(str_replace('_', ' ', $story->status)) }}
            </span>
        </div>

        <!-- Global Action Buttons -->
        @php
            $firstEpisode = $story->episodes->first();
            $totalBoards = $firstEpisode ? $firstEpisode->boards->count() : 0;
            $readyBoards = $firstEpisode ? $firstEpisode->boards->where('status', 'ready')->count() : 0;
        @endphp
        <div class="flex flex-wrap items-center gap-2.5">
            <button 
                wire:click="generateAllAssets" 
                class="inline-flex items-center gap-1.5 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 px-3.5 py-2 text-xs font-bold text-white shadow-sm hover:from-blue-700 hover:to-indigo-700 transition-all"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                </svg>
                {{ __('⚡ Generate All') }}
            </button>

            <!-- Regenerate All Scene Assets with SweetAlert2 Confirmation -->
            <button 
                type="button"
                onclick="triggerSwalRegenerateAll()"
                class="inline-flex items-center gap-1.5 rounded-xl bg-zinc-100 hover:bg-zinc-200 text-zinc-800 dark:bg-zinc-800 dark:hover:bg-zinc-700 dark:text-zinc-200 px-3.5 py-2 text-xs font-bold border border-zinc-300 dark:border-zinc-700 shadow-sm transition-all"
            >
                <svg class="h-4 w-4 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                {{ __('🔄 Regenerate All Scene Assets') }}
            </button>

            @if ($firstEpisode)
                <button 
                    wire:click="renderFinalVideo({{ $firstEpisode->id }})" 
                    class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-purple-600 to-pink-600 px-4 py-2 text-xs font-bold text-white shadow-sm hover:from-purple-700 hover:to-pink-700 transition-all disabled:opacity-50"
                    @disabled($readyBoards < $totalBoards || $totalBoards === 0)
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                    {{ __('🎬 Render Final Video') }} ({{ $readyBoards }}/{{ $totalBoards }})
                </button>
            @endif

            <!-- SEO & Social Kit Button -->
            <button 
                wire:click="openSeoModal" 
                type="button"
                class="inline-flex items-center gap-1.5 rounded-xl bg-indigo-50 hover:bg-indigo-100 text-indigo-700 dark:bg-indigo-950/40 dark:hover:bg-indigo-900/60 dark:text-indigo-300 px-3.5 py-2 text-xs font-bold border border-indigo-200 dark:border-indigo-800/60 shadow-sm transition-all"
            >
                <flux:icon name="sparkles" class="h-4 w-4 text-indigo-500" />
                {{ __('✨ SEO & Social Kit') }}
            </button>

            <!-- Thumbnail Generator Button -->
            <button 
                wire:click="openThumbnailModal" 
                type="button"
                class="inline-flex items-center gap-1.5 rounded-xl bg-amber-50 hover:bg-amber-100 text-amber-800 dark:bg-amber-950/40 dark:hover:bg-amber-900/60 dark:text-amber-300 px-3.5 py-2 text-xs font-bold border border-amber-200 dark:border-amber-800/60 shadow-sm transition-all"
            >
                <flux:icon name="photo" class="h-4 w-4 text-amber-500" />
                {{ __('🖼️ Thumbnail Generator') }}
            </button>

            <!-- 1-Click Multi-Language Duplicate Button -->
            <button 
                wire:click="openDuplicateModal" 
                type="button"
                class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:hover:bg-emerald-900/60 dark:text-emerald-300 px-3.5 py-2 text-xs font-bold border border-emerald-200 dark:border-emerald-800/60 shadow-sm transition-all"
            >
                <flux:icon name="globe-alt" class="h-4 w-4 text-emerald-600 dark:text-emerald-400" />
                {{ __('🌐 Clone to Language') }}
            </button>
        </div>
    </div>

    <!-- Episode Story Header Card -->
    <div class="relative overflow-hidden rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="space-y-1.5">
                <div class="flex items-center gap-2">
                    <span class="rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-bold text-blue-800 dark:bg-blue-950/40 dark:text-blue-400">
                        {{ $story->contentTitle ? $story->contentTitle->title : 'Standalone Story' }}
                    </span>
                    <span class="text-zinc-300 dark:text-zinc-700">•</span>
                    <span class="rounded-full bg-purple-100 px-2.5 py-0.5 text-xs font-bold text-purple-800 dark:bg-purple-950/40 dark:text-purple-400">
                        {{ $story->visual_style }}
                    </span>
                    <span class="rounded-full bg-zinc-100 px-2.5 py-0.5 text-xs font-bold text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                        📐 {{ $story->aspect_ratio }}
                    </span>
                </div>

                <flux:heading size="xl" level="1" class="font-bold text-zinc-900 dark:text-zinc-100">
                    {{ $story->title }}
                </flux:heading>

                @if ($story->premise)
                    <flux:subheading class="text-xs max-w-4xl text-zinc-500 line-clamp-2">{{ $story->premise }}</flux:subheading>
                @endif
            </div>

            <!-- Asset Readiness Progress Meter -->
            <div class="flex flex-col items-start lg:items-end gap-1 shrink-0">
                <span class="text-xs font-bold text-zinc-700 dark:text-zinc-300">
                    {{ __('Story Assets Readiness:') }} <strong class="text-purple-600 dark:text-purple-400">{{ $readyBoards }}/{{ $totalBoards }}</strong>
                </span>
                <div class="h-2 w-48 rounded-full bg-zinc-100 dark:bg-zinc-800 overflow-hidden">
                    <div 
                        class="h-full bg-gradient-to-r from-purple-500 to-emerald-500 transition-all duration-500"
                        style="width: {{ $totalBoards > 0 ? round(($readyBoards / $totalBoards) * 100) : 0 }}%"
                    ></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Completed Video Player Banner -->
    @if ($firstEpisode && $firstEpisode->video_path)
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50/50 p-6 shadow-sm dark:border-emerald-900 dark:bg-emerald-950/20">
            <div class="flex flex-col md:flex-row items-center gap-6">
                <div class="relative overflow-hidden rounded-xl bg-black shadow-lg {{ $story->aspect_ratio === '9:16' ? 'w-64 max-h-96 aspect-[9/16]' : 'w-full max-w-md aspect-video' }}">
                    <video controls playsinline src="{{ $firstEpisode->video_url }}" class="h-full w-full object-contain"></video>
                </div>
                <div class="space-y-3">
                    <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300">
                        ✓ {{ __('Final Manhwa Recap Video Rendered') }}
                    </span>
                    <h3 class="text-base font-bold text-zinc-900 dark:text-zinc-100">{{ $firstEpisode->title }}</h3>
                    <p class="text-xs text-zinc-600 dark:text-zinc-400">{{ __('Your cinematic comic recap video is complete with synchronized voiceover narration, smooth camera panning, and animated subtitles.') }}</p>
                    <a href="{{ $firstEpisode->video_url }}" download class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-bold text-white hover:bg-emerald-700">
                        ⬇️ {{ __('Download MP4 Video') }}
                    </a>
                </div>
            </div>
        </div>
    @endif

    <!-- ======================================================= -->
    <!-- SPLIT-VIEW STORYBOARD DIRECTOR                          -->
    <!-- ======================================================= -->
    @if ($firstEpisode && $firstEpisode->boards->isNotEmpty())
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-12 items-start">
            
            <!-- LEFT PANEL: SCRIPT SEQUENCE & TIMELINE (5 cols) -->
            <div class="lg:col-span-5 space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                        <span>🎬</span> {{ __('Scene Timeline & Comic Panels') }} ({{ $totalBoards }})
                    </h3>
                    <span class="text-xs text-zinc-400">{{ __('Click a panel to inspect') }}</span>
                </div>

                <div class="space-y-3 max-h-[78vh] overflow-y-auto pr-1">
                    @foreach ($firstEpisode->boards as $board)
                        @php
                            $isSelected = $activeBoardId === $board->id;
                            $hasImage = ! empty($board->image_path);
                            $hasAudio = ! empty($board->audio_path);
                            $isReady = $board->status === 'ready';
                        @endphp
                        <div 
                            wire:click="selectBoard({{ $board->id }})"
                            class="cursor-pointer rounded-2xl border p-4 transition-all {{ $isSelected ? 'border-purple-500 bg-purple-50/30 shadow-md dark:border-purple-500 dark:bg-purple-950/20 ring-2 ring-purple-500/20' : 'border-zinc-200 bg-white hover:border-zinc-300 dark:border-zinc-700 dark:bg-zinc-900' }}"
                        >
                            <div class="flex items-start gap-3">
                                <!-- Thumbnail Snapshot -->
                                <div class="relative h-16 w-24 shrink-0 overflow-hidden rounded-xl bg-zinc-950 border border-zinc-200 dark:border-zinc-800 flex items-center justify-center">
                                    @if ($hasImage)
                                        <img src="{{ $board->image_url }}" alt="Board {{ $board->board_number }}" class="h-full w-full object-cover">
                                    @else
                                        <span class="text-xs font-mono text-zinc-500">#{{ $board->board_number }}</span>
                                    @endif

                                    @if ($hasAudio)
                                        <span class="absolute bottom-1 right-1 rounded bg-black/70 px-1 text-[9px] text-white">🎙️</span>
                                    @endif
                                </div>

                                <!-- Narration Chunk & Meta -->
                                <div class="flex-1 min-w-0 space-y-1.5">
                                    <div class="flex items-center justify-between gap-1">
                                        <span class="text-xs font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-1.5">
                                            <span>#{{ $board->board_number }}</span>
                                            <span class="text-[10px] uppercase font-semibold text-purple-600 dark:text-purple-400">{{ str_replace('_', ' ', $board->view_type ?? 'panel') }}</span>
                                        </span>
                                        <span class="rounded-full px-2 py-0.5 text-[10px] font-bold {{ $isReady ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300' : 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400' }}">
                                            {{ $isReady ? '✓ Ready' : 'Draft' }}
                                        </span>
                                    </div>

                                    <p class="text-xs text-zinc-600 dark:text-zinc-300 line-clamp-2 leading-relaxed">
                                        "{{ $board->narration_chunk }}"
                                    </p>

                                    <!-- Attribution Tags -->
                                    <div class="flex flex-wrap items-center gap-1 pt-0.5">
                                        @if (! empty($board->characters_present))
                                            @foreach ($board->characters_present as $charName)
                                                <span class="rounded bg-purple-50 px-1.5 py-0.5 text-[9px] font-semibold text-purple-700 dark:bg-purple-950/50 dark:text-purple-300">
                                                    👤 {{ $charName }}
                                                </span>
                                            @endforeach
                                        @endif
                                        @if ($board->environment)
                                            <span class="rounded bg-emerald-50 px-1.5 py-0.5 text-[9px] font-semibold text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300 truncate max-w-[120px]">
                                                🏞️ {{ $board->environment->name }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- RIGHT PANEL: ACTIVE COMIC PANEL DIRECTOR (7 cols) -->
            <div class="lg:col-span-7 space-y-5 sticky top-6">
                @if ($activeBoard)
                    <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 space-y-5">
                        
                        <!-- Panel Director Header -->
                        <div class="flex items-center justify-between border-b border-zinc-100 pb-3 dark:border-zinc-800">
                            <div>
                                <h3 class="text-base font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                                    <span>🎨</span> {{ __('Panel #').$activeBoard->board_number.': '.__('Comic Director') }}
                                </h3>
                                <p class="text-xs text-zinc-500">{{ __('Active comic visual composition, bound asset sheets, and prompt inspector.') }}</p>
                            </div>

                            <div class="flex items-center gap-2">
                                <button 
                                    wire:click="regenerateImage({{ $activeBoard->id }})" 
                                    class="rounded-xl bg-blue-50 px-3 py-1.5 text-xs font-bold text-blue-700 hover:bg-blue-100 dark:bg-blue-950/50 dark:text-blue-300 transition-all flex items-center gap-1"
                                >
                                    <span>🎨</span> {{ __('Re-render Image') }}
                                </button>
                                <button 
                                    wire:click="regenerateVoice({{ $activeBoard->id }})" 
                                    class="rounded-xl bg-purple-50 px-3 py-1.5 text-xs font-bold text-purple-700 hover:bg-purple-100 dark:bg-purple-950/50 dark:text-purple-300 transition-all flex items-center gap-1"
                                >
                                    <span>🎙️</span> {{ __('Re-voice') }}
                                </button>
                            </div>
                        </div>

                        <!-- 16:9 Comic Artwork Canvas Display -->
                        <div class="relative overflow-hidden rounded-2xl bg-zinc-950 aspect-video flex items-center justify-center border border-zinc-200 dark:border-zinc-800 shadow-inner group">
                            @if ($activeBoard->image_path)
                                <img src="{{ $activeBoard->image_url }}" alt="Board {{ $activeBoard->board_number }}" class="h-full w-full object-cover">
                            @else
                                <div class="text-center p-6 space-y-3">
                                    <span class="text-4xl block">🖼️</span>
                                    <span class="text-xs text-zinc-400 block">{{ __('No comic panel artwork generated yet.') }}</span>
                                    <button 
                                        wire:click="regenerateImage({{ $activeBoard->id }})" 
                                        class="rounded-xl bg-blue-600 px-4 py-2 text-xs font-bold text-white hover:bg-blue-700 shadow-sm"
                                    >
                                        ✨ {{ __('Render This Panel Artwork') }}
                                    </button>
                                </div>
                            @endif
                        </div>

                        <!-- Audio Player & Narration Chunk Bar -->
                        <div class="rounded-xl bg-zinc-50 p-4 dark:bg-zinc-800/60 space-y-2 border border-zinc-100 dark:border-zinc-800">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-zinc-700 dark:text-zinc-300 flex items-center gap-1.5">
                                    <span>🎙️</span> {{ __('Spoken Narration Dialogue') }}
                                </span>
                                <span class="text-[11px] text-zinc-400 font-mono">{{ $story->voice_name }}</span>
                            </div>

                            <p class="text-xs font-medium text-zinc-800 dark:text-zinc-200 italic leading-relaxed">
                                "{{ $activeBoard->narration_chunk }}"
                            </p>

                            @if ($activeBoard->audio_path)
                                <audio controls class="w-full h-8 pt-1" src="{{ $activeBoard->audio_url }}"></audio>
                            @endif
                        </div>

                        <!-- Live Consistency Payload Inspector -->
                        <div class="rounded-2xl border border-purple-200/80 bg-gradient-to-br from-purple-50/40 via-white to-indigo-50/30 p-4 dark:border-purple-900/50 dark:from-purple-950/20 dark:via-zinc-900 dark:to-indigo-950/20 space-y-3.5 shadow-sm">
                            <div class="flex items-center justify-between border-b border-purple-100 pb-2.5 dark:border-purple-900/40">
                                <div>
                                    <h4 class="text-xs font-bold text-purple-950 dark:text-purple-200 uppercase tracking-wider flex items-center gap-1.5">
                                        <span>📋</span> {{ __('Live Consistency Payload Inspector') }}
                                    </h4>
                                    <p class="text-[11px] text-zinc-500">{{ __('Real-time World Bible reference sheets & tokens transmitted to AI Image Generator for this panel.') }}</p>
                                </div>
                                <div class="flex items-center gap-2">
                                    @if ($this->availableCharacters->isNotEmpty())
                                        <div class="relative" x-data="{ open: false }">
                                            <button @click="open = !open" type="button" class="text-[10px] font-bold text-purple-700 hover:text-purple-800 dark:text-purple-300 flex items-center gap-1 bg-purple-100 dark:bg-purple-950/60 px-2 py-0.5 rounded-lg border border-purple-300 dark:border-purple-800 transition-colors">
                                                <span>+</span> {{ __('Bind Character') }}
                                            </button>
                                            <div x-show="open" @click.away="open = false" class="absolute right-0 mt-1 w-48 rounded-xl bg-white dark:bg-zinc-800 shadow-xl border border-zinc-200 dark:border-zinc-700 py-1 z-30 text-xs" style="display: none;">
                                                @foreach ($this->availableCharacters as $availChar)
                                                    <button 
                                                        type="button" 
                                                        wire:click="bindCharacterToBoard({{ $activeBoard->id }}, {{ $availChar->id }}); open = false;"
                                                        class="w-full text-left px-3 py-1.5 hover:bg-purple-50 dark:hover:bg-purple-950/40 flex items-center justify-between"
                                                    >
                                                        <span class="truncate font-medium text-zinc-900 dark:text-zinc-100">{{ $availChar->name }}</span>
                                                        <span class="text-[9px] text-purple-500 font-mono">{{ $availChar->entity_type }}</span>
                                                    </button>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                    <span class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-[10px] font-bold text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 flex items-center gap-1">
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                        {{ __('Dual Ref Active') }}
                                    </span>
                                </div>
                            </div>

                            <!-- Reference Sheets Grid -->
                            <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-2">
                                <!-- Bound Characters & Creatures Sheets -->
                                @if (! empty($activeBoard->characters_present))
                                    @foreach ($activeBoard->characters_present as $charName)
                                        @php
                                            $charModel = $this->findCharacterByName($charName);
                                        @endphp
                                        @if ($charModel)
                                            <div 
                                                wire:click="inspectAsset('character', {{ $charModel->id }})"
                                                class="flex items-center gap-2.5 rounded-xl border border-purple-200 bg-white/90 p-2.5 dark:border-purple-800/60 dark:bg-zinc-900/90 shadow-xs cursor-pointer hover:border-purple-400 transition-all group relative"
                                            >
                                                <div class="relative h-12 w-16 shrink-0 overflow-hidden rounded-lg bg-zinc-950 border border-purple-100 dark:border-zinc-800">
                                                    @if ($charModel->sheet_image_url || $charModel->avatar_url)
                                                        <img src="{{ $charModel->sheet_image_url ?? $charModel->avatar_url }}" alt="{{ $charName }}" class="h-full w-full object-cover group-hover:scale-105 transition-transform">
                                                    @else
                                                        <div class="flex h-full w-full items-center justify-center text-base">👤</div>
                                                    @endif
                                                </div>
                                                <div class="flex-1 min-w-0 space-y-0.5">
                                                    <div class="flex items-center justify-between pr-4">
                                                        <span class="text-xs font-bold text-zinc-900 dark:text-zinc-100 truncate">{{ $charModel->name }}</span>
                                                        <span class="rounded bg-purple-100 px-1 text-[9px] font-bold text-purple-800 dark:bg-purple-950 dark:text-purple-300">
                                                            {{ $charModel->entity_type }}
                                                        </span>
                                                    </div>
                                                    <p class="text-[10px] text-zinc-500 truncate font-mono" title="{{ $charModel->getConsistencyTokens() }}">
                                                        {{ $charModel->getConsistencyTokens() }}
                                                    </p>
                                                </div>
                                                <button 
                                                    type="button"
                                                    wire:click.stop="unbindCharacterFromBoard({{ $activeBoard->id }}, '{{ $charName }}')"
                                                    title="{{ __('Remove from panel') }}"
                                                    class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 text-zinc-400 hover:text-red-500 text-xs font-bold p-1 rounded transition-opacity"
                                                >
                                                    ✕
                                                </button>
                                            </div>
                                        @else
                                            <div class="col-span-full flex items-center justify-between gap-2 rounded-xl border border-amber-300 bg-amber-50/50 p-2.5 dark:border-amber-800/60 dark:bg-amber-950/20 shadow-xs">
                                                <div class="flex items-center gap-2 min-w-0">
                                                    <div class="h-9 w-9 shrink-0 rounded-lg bg-amber-100 dark:bg-amber-900/50 flex items-center justify-center text-sm">⚠️</div>
                                                    <div class="min-w-0">
                                                        <p class="text-xs font-bold text-amber-900 dark:text-amber-200 truncate">Unmatched: "{{ $charName }}"</p>
                                                        <p class="text-[10px] text-amber-600 dark:text-amber-400">Not linked to World Bible character</p>
                                                    </div>
                                                </div>
                                                @if ($this->availableCharacters->isNotEmpty())
                                                    <select 
                                                        wire:change="bindCharacterToBoard({{ $activeBoard->id }}, $event.target.value)" 
                                                        class="text-[10px] rounded-lg border border-amber-300 dark:border-amber-700 bg-white dark:bg-zinc-800 py-1 px-1.5 text-zinc-800 dark:text-zinc-200"
                                                    >
                                                        <option value="">{{ __('Re-bind to...') }}</option>
                                                        @foreach ($this->availableCharacters as $availChar)
                                                            <option value="{{ $availChar->id }}">{{ $availChar->name }}</option>
                                                        @endforeach
                                                    </select>
                                                @endif
                                            </div>
                                        @endif
                                    @endforeach
                                @else
                                    <div class="col-span-full rounded-xl border border-dashed border-purple-200 dark:border-purple-800/60 bg-purple-50/30 dark:bg-purple-950/20 p-3 text-center space-y-1.5">
                                        <p class="text-[11px] text-zinc-500">{{ __('No characters bound to this panel.') }}</p>
                                        @if ($this->availableCharacters->isNotEmpty())
                                            <div class="flex flex-wrap justify-center gap-1.5">
                                                @foreach ($this->availableCharacters as $availChar)
                                                    <button 
                                                        type="button" 
                                                        wire:click="bindCharacterToBoard({{ $activeBoard->id }}, {{ $availChar->id }})"
                                                        class="text-[10px] font-bold text-purple-700 dark:text-purple-300 bg-purple-100 dark:bg-purple-900/60 px-2 py-0.5 rounded-md hover:bg-purple-200 transition-colors"
                                                    >
                                                        + {{ $availChar->name }}
                                                    </button>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                @endif

                                <!-- Bound Environment Set Sheet -->
                                @if ($activeBoard->environment)
                                    @php
                                        $envModel = $activeBoard->environment;
                                    @endphp
                                    <div 
                                        wire:click="inspectAsset('environment', {{ $envModel->id }})"
                                        class="flex items-center gap-2.5 rounded-xl border border-emerald-200 bg-white/90 p-2.5 dark:border-emerald-800/60 dark:bg-zinc-900/90 shadow-xs cursor-pointer hover:border-emerald-400 transition-all group"
                                    >
                                        <div class="relative h-12 w-16 shrink-0 overflow-hidden rounded-lg bg-zinc-950 border border-emerald-100 dark:border-zinc-800">
                                            @if ($envModel->sheet_image_url || $envModel->image_url)
                                                <img src="{{ $envModel->sheet_image_url ?? $envModel->image_url }}" alt="{{ $envModel->name }}" class="h-full w-full object-cover group-hover:scale-105 transition-transform">
                                            @else
                                                <div class="flex h-full w-full items-center justify-center text-base">🏞️</div>
                                            @endif
                                        </div>
                                        <div class="flex-1 min-w-0 space-y-0.5">
                                            <div class="flex items-center justify-between">
                                                <span class="text-xs font-bold text-zinc-900 dark:text-zinc-100 truncate">{{ $envModel->name }}</span>
                                                <span class="rounded bg-emerald-100 px-1 text-[9px] font-bold text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                                                    {{ $envModel->setting_type }}
                                                </span>
                                            </div>
                                            <p class="text-[10px] text-zinc-500 truncate font-mono">
                                                {{ $envModel->getConsistencyTokens() }}
                                            </p>
                                        </div>
                                    </div>
                                @endif

                                <!-- Bound Props Sheet -->
                                @if (! empty($activeBoard->props_present) && $story->contentTitle)
                                    @foreach ($activeBoard->props_present as $propName)
                                        @php
                                            $propModel = $story->contentTitle->props->firstWhere('name', $propName);
                                        @endphp
                                        <div 
                                            @if ($propModel) wire:click="inspectAsset('prop', {{ $propModel->id }})" @endif
                                            class="flex items-center gap-2.5 rounded-xl border border-amber-200 bg-white/90 p-2.5 dark:border-amber-800/60 dark:bg-zinc-900/90 shadow-xs cursor-pointer hover:border-amber-400 transition-all group"
                                        >
                                            <div class="relative h-12 w-16 shrink-0 overflow-hidden rounded-lg bg-zinc-950 border border-amber-100 dark:border-zinc-800">
                                                @if ($propModel && ($propModel->sheet_image_url || $propModel->image_url))
                                                    <img src="{{ $propModel->sheet_image_url ?? $propModel->image_url }}" alt="{{ $propName }}" class="h-full w-full object-cover group-hover:scale-105 transition-transform">
                                                @else
                                                    <div class="flex h-full w-full items-center justify-center text-base">⚔️</div>
                                                @endif
                                            </div>
                                            <div class="flex-1 min-w-0 space-y-0.5">
                                                <div class="flex items-center justify-between">
                                                    <span class="text-xs font-bold text-zinc-900 dark:text-zinc-100 truncate">{{ $propName }}</span>
                                                    <span class="rounded bg-amber-100 px-1 text-[9px] font-bold text-amber-800 dark:bg-amber-950 dark:text-amber-300">
                                                        {{ $propModel ? $propModel->category : 'Prop' }}
                                                    </span>
                                                </div>
                                                <p class="text-[10px] text-zinc-500 truncate font-mono">
                                                    {{ $propModel ? $propModel->getConsistencyTokens() : 'No tokens bound' }}
                                                </p>
                                            </div>
                                        </div>
                                    @endforeach
                                @endif
                            </div>

                            <!-- Art Direction Blueprint in Effect -->
                            @if ($story->contentTitle)
                                <div class="flex flex-wrap items-center gap-2 pt-1 border-t border-purple-100/60 dark:border-purple-900/30 text-[11px]">
                                    <span class="font-bold text-zinc-600 dark:text-zinc-400">🎨 {{ __('Art Rules:') }}</span>
                                    @if ($story->contentTitle->color_palette)
                                        <span class="rounded-md bg-zinc-100 dark:bg-zinc-800 px-2 py-0.5 font-medium text-zinc-700 dark:text-zinc-300">
                                            🌈 {{ $story->contentTitle->color_palette }}
                                        </span>
                                    @endif
                                    @if ($story->contentTitle->lighting_mood)
                                        <span class="rounded-md bg-zinc-100 dark:bg-zinc-800 px-2 py-0.5 font-medium text-zinc-700 dark:text-zinc-300">
                                            💡 {{ $story->contentTitle->lighting_mood }}
                                        </span>
                                    @endif
                                    @if ($story->contentTitle->camera_framing)
                                        <span class="rounded-md bg-zinc-100 dark:bg-zinc-800 px-2 py-0.5 font-medium text-zinc-700 dark:text-zinc-300">
                                            📐 {{ $story->contentTitle->camera_framing }}
                                        </span>
                                    @endif
                                </div>
                            @endif
                        </div>

                        <!-- Interactive Prompt Studio & Camera Controls -->
                        <div class="space-y-4 pt-1">
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <div>
                                    <flux:select wire:model="editingViewType" :label="__('Comic Panel Staging / Shot Type')">
                                        <flux:select.option value="two_shot_interaction">👥 {{ __('Two-Shot Interaction (Dialogue/Confrontation)') }}</flux:select.option>
                                        <flux:select.option value="over_the_shoulder">🎬 {{ __('Over-the-Shoulder (OTS Depth)') }}</flux:select.option>
                                        <flux:select.option value="wide_establishing">🏞️ {{ __('Wide Establishing (Environment Focus)') }}</flux:select.option>
                                        <flux:select.option value="dynamic_action_clash">💥 {{ __('Dynamic Action Clash / Attack') }}</flux:select.option>
                                        <flux:select.option value="dramatic_close_up">👁️ {{ __('Dramatic Close-Up Reaction') }}</flux:select.option>
                                        <flux:select.option value="dutch_angle_tension">📐 {{ __('Dutch Angle (Tension/Mystery)') }}</flux:select.option>
                                        <flux:select.option value="low_angle_hero">⚡ {{ __('Low Angle Heroic Stature') }}</flux:select.option>
                                    </flux:select>
                                </div>
                                <div>
                                    <flux:select wire:model="editingCameraMovement" :label="__('Video Motion Directives')">
                                        <flux:select.option value="zoom_in">🔍 {{ __('Slow Zoom In') }}</flux:select.option>
                                        <flux:select.option value="zoom_out">🔎 {{ __('Slow Zoom Out') }}</flux:select.option>
                                        <flux:select.option value="pan_left">⬅️ {{ __('Pan Left') }}</flux:select.option>
                                        <flux:select.option value="pan_right">➡️ {{ __('Pan Right') }}</flux:select.option>
                                        <flux:select.option value="vertical_scroll">⬇️ {{ __('Vertical Webtoon Scroll') }}</flux:select.option>
                                        <flux:select.option value="static_focus">🎯 {{ __('Static Focus') }}</flux:select.option>
                                    </flux:select>
                                </div>
                            </div>

                            <div>
                                <flux:textarea 
                                    wire:model="editingPrompt" 
                                    :label="__('Compiled Final Prompt (Read-only Preview)')" 
                                    rows="4" 
                                    class="font-mono text-xs opacity-75"
                                    readonly
                                />
                            </div>

                            <div class="flex justify-end gap-2 pt-1">
                                <flux:button wire:click="saveActiveBoard" variant="primary" class="bg-purple-600 hover:bg-purple-700 text-white font-bold">
                                    💾 {{ __('Save Panel Settings') }}
                                </flux:button>
                                <flux:button wire:click="regenerateImage({{ $activeBoard->id }})" variant="primary" class="bg-blue-600 hover:bg-blue-700 text-white font-bold">
                                    🎨 {{ __('Re-render Artwork') }}
                                </flux:button>
                            </div>
                        </div>

                    </div>
                @else
                    <div class="rounded-2xl border border-dashed border-zinc-300 p-12 text-center dark:border-zinc-700">
                        <span class="text-3xl block mb-2">🎬</span>
                        <h4 class="text-sm font-bold text-zinc-900 dark:text-zinc-100">{{ __('No panel selected') }}</h4>
                        <p class="text-xs text-zinc-500">{{ __('Click any scene board in the timeline on the left to start editing.') }}</p>
                    </div>
                @endif
            </div>

        </div>
    @endif

    <!-- ======================================================= -->
    <!-- MODEL REFERENCE SHEET INSPECTOR MODAL                   -->
    <!-- ======================================================= -->
    @if ($showAssetModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4 backdrop-blur-md overflow-y-auto">
            <div class="w-full max-w-4xl max-h-[90vh] overflow-y-auto rounded-2xl border border-zinc-700 bg-zinc-900 p-6 shadow-2xl text-zinc-100 space-y-6">
                <div class="flex items-center justify-between border-b border-zinc-800 pb-3">
                    <div>
                        <h3 class="text-lg font-bold flex items-center gap-2">
                            <span>📐</span> {{ $inspectingSheetTitle }}
                        </h3>
                        <div class="flex items-center gap-2 mt-1">
                            <span class="text-xs text-zinc-400">{{ __('World Bible Reference Blueprint') }}</span>
                            @if ($inspectingTokenTier === 'blueprint_verified')
                                <span class="rounded-full bg-emerald-950/60 border border-emerald-500/40 px-2 py-0.5 text-[10px] font-bold text-emerald-300 flex items-center gap-1">
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                    🌟 {{ __('Verified Turnaround Sheet') }}
                                </span>
                            @elseif ($inspectingTokenTier === 'world_bible_profile')
                                <span class="rounded-full bg-blue-950/60 border border-blue-500/40 px-2 py-0.5 text-[10px] font-bold text-blue-300 flex items-center gap-1">
                                    🛡️ {{ __('World Bible Profile Tokens') }}
                                </span>
                            @else
                                <span class="rounded-full bg-purple-950/60 border border-purple-500/40 px-2 py-0.5 text-[10px] font-bold text-purple-300 flex items-center gap-1">
                                    ⚙️ {{ __('Entity Baseline Tokens') }}
                                </span>
                            @endif
                        </div>
                    </div>
                    <button wire:click="$set('showAssetModal', false)" class="text-zinc-400 hover:text-zinc-200">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                @if ($inspectingSheetImageUrl)
                    <div class="overflow-hidden rounded-xl border border-zinc-800 bg-black aspect-video max-h-96 flex items-center justify-center">
                        <img src="{{ $inspectingSheetImageUrl }}" alt="{{ $inspectingSheetTitle }}" class="h-full w-full object-contain">
                    </div>
                @endif

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 text-xs">
                    <!-- Consistency Prompt Tokens Box (ALWAYS DISPLAYED) -->
                    <div class="col-span-full rounded-xl border border-purple-500/40 bg-purple-950/20 p-4 space-y-2">
                        <div class="flex items-center justify-between">
                            <h4 class="font-bold text-purple-300 uppercase tracking-wider text-[11px] flex items-center gap-1.5">
                                <span>🔒</span> {{ __('Active Consistency Prompt Tokens') }}
                            </h4>
                            <span class="text-[10px] text-purple-400 font-mono">{{ __('Transmitted into AI Image Generator Visual Prompt') }}</span>
                        </div>
                        <div class="rounded-lg bg-black/60 p-3 font-mono text-xs text-purple-200 select-all border border-purple-900/40">
                            {{ $inspectingSheetTokens ?? ($inspectingSheetData['consistency_prompt_tokens'] ?? 'Active World Bible Profile Tokens') }}
                        </div>
                    </div>

                    @if ($inspectingSheetData && ! empty($inspectingSheetData['three_views']))
                        <div class="rounded-xl border border-zinc-800 bg-zinc-950 p-4 space-y-2">
                            <h4 class="font-bold text-purple-400 uppercase tracking-wider text-[11px]">
                                🔄 {{ __('Three Views Turnaround') }}
                            </h4>
                            <div class="space-y-1 text-zinc-300">
                                <p><strong>Front:</strong> {{ $inspectingSheetData['three_views']['front'] ?? 'N/A' }}</p>
                                <p><strong>Side:</strong> {{ $inspectingSheetData['three_views']['side'] ?? 'N/A' }}</p>
                                <p><strong>Back:</strong> {{ $inspectingSheetData['three_views']['back'] ?? 'N/A' }}</p>
                            </div>
                        </div>
                    @endif

                    @if ($inspectingSheetData && ! empty($inspectingSheetData['color_palette']))
                        <div class="rounded-xl border border-zinc-800 bg-zinc-950 p-4 space-y-2">
                            <h4 class="font-bold text-blue-400 uppercase tracking-wider text-[11px]">
                                🎨 {{ __('Color Palette') }}
                            </h4>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($inspectingSheetData['color_palette'] as $swatch)
                                    <span class="rounded bg-zinc-800 px-2 py-1 text-[11px] font-medium">
                                        {{ is_array($swatch) ? ($swatch['name'].': '.$swatch['color']) : $swatch }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                <div class="flex justify-end border-t border-zinc-800 pt-3">
                    <button wire:click="$set('showAssetModal', false)" class="rounded-xl bg-zinc-800 px-5 py-2 text-xs font-bold text-white hover:bg-zinc-700">
                        {{ __('Close') }}
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- SEO & Social Kit Modal -->
    @if ($showSeoModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm" x-data x-on:keydown.escape.window="$wire.closeSeoModal()">
            <div class="relative max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl border border-zinc-200 bg-white p-6 shadow-2xl dark:border-zinc-800 dark:bg-zinc-900 sm:p-7">
                <button
                    type="button"
                    wire:click="closeSeoModal"
                    class="absolute right-4 top-4 rounded-xl p-2 text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800"
                >
                    <flux:icon name="x-mark" class="h-5 w-5" />
                </button>

                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/40 dark:text-indigo-400">
                        <flux:icon name="sparkles" class="h-6 w-6" />
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-zinc-900 dark:text-zinc-100">{{ __('SEO & Viral Social Kit') }}</h3>
                        <p class="text-xs text-zinc-500">{{ __('AI-engineered for high YouTube CTR, search ranking, and TikTok retention.') }}</p>
                    </div>
                </div>

                @if ($seoData)
                    <div class="mt-6 space-y-4 text-xs">
                        <!-- 2-Second Retention Hook -->
                        <div class="rounded-xl border border-amber-300/40 bg-amber-50/60 p-3.5 dark:border-amber-500/30 dark:bg-amber-950/20">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-amber-800 dark:text-amber-400">⚡ {{ __('First 2-Second Retention Hook Line') }}</span>
                                <button
                                    type="button"
                                    x-data="{ copied: false }"
                                    x-on:click="navigator.clipboard.writeText('{{ addslashes($seoData['hook_line']) }}'); copied = true; setTimeout(() => copied = false, 2000)"
                                    class="rounded-md border border-amber-300 bg-white px-2 py-0.5 text-[11px] font-semibold text-amber-900 hover:bg-amber-50 dark:border-amber-700 dark:bg-zinc-800 dark:text-amber-300"
                                >
                                    <span x-show="!copied">{{ __('Copy') }}</span>
                                    <span x-show="copied" class="text-emerald-500">{{ __('Copied!') }}</span>
                                </button>
                            </div>
                            <p class="mt-1 font-medium text-zinc-800 dark:text-zinc-200">"{{ $seoData['hook_line'] }}"</p>
                        </div>

                        <!-- YouTube Title -->
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="font-semibold text-zinc-700 dark:text-zinc-300">{{ __('YouTube High-CTR Title') }}</label>
                                <button
                                    type="button"
                                    x-data="{ copied: false }"
                                    x-on:click="navigator.clipboard.writeText('{{ addslashes($seoData['title']) }}'); copied = true; setTimeout(() => copied = false, 2000)"
                                    class="rounded border border-zinc-200 bg-zinc-50 px-2 py-0.5 text-[11px] font-semibold text-zinc-600 hover:bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300"
                                >
                                    <span x-show="!copied">{{ __('Copy Title') }}</span>
                                    <span x-show="copied" class="text-emerald-500">{{ __('Copied!') }}</span>
                                </button>
                            </div>
                            <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-3 font-semibold text-zinc-900 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100">
                                {{ $seoData['title'] }}
                            </div>
                        </div>

                        <!-- YouTube Description -->
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="font-semibold text-zinc-700 dark:text-zinc-300">{{ __('YouTube Description & Timestamps') }}</label>
                                <button
                                    type="button"
                                    x-data="{ copied: false }"
                                    x-on:click="navigator.clipboard.writeText('{{ addslashes($seoData['description']) }}'); copied = true; setTimeout(() => copied = false, 2000)"
                                    class="rounded border border-zinc-200 bg-zinc-50 px-2 py-0.5 text-[11px] font-semibold text-zinc-600 hover:bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300"
                                >
                                    <span x-show="!copied">{{ __('Copy Description') }}</span>
                                    <span x-show="copied" class="text-emerald-500">{{ __('Copied!') }}</span>
                                </button>
                            </div>
                            <textarea
                                readonly
                                rows="6"
                                class="w-full rounded-xl border border-zinc-200 bg-zinc-50 p-3 text-xs leading-relaxed text-zinc-800 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200 font-mono"
                            >{{ $seoData['description'] }}</textarea>
                        </div>

                        <!-- Tags & TikTok Hashtags -->
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div>
                                <label class="font-semibold text-zinc-700 dark:text-zinc-300 block mb-1.5">{{ __('YouTube Search Tags') }}</label>
                                <div class="flex flex-wrap gap-1.5 rounded-xl border border-zinc-200 bg-zinc-50 p-2.5 dark:border-zinc-700 dark:bg-zinc-800">
                                    @foreach ($seoData['tags'] as $tag)
                                        <span class="rounded bg-zinc-200 px-2 py-0.5 text-[10px] font-medium text-zinc-700 dark:bg-zinc-700 dark:text-zinc-300">{{ $tag }}</span>
                                    @endforeach
                                </div>
                            </div>

                            <div>
                                <label class="font-semibold text-zinc-700 dark:text-zinc-300 block mb-1.5">{{ __('TikTok & Shorts Hashtags') }}</label>
                                <div class="flex flex-wrap gap-1.5 rounded-xl border border-zinc-200 bg-zinc-50 p-2.5 dark:border-zinc-700 dark:bg-zinc-800">
                                    @foreach ($seoData['hashtags'] as $ht)
                                        <span class="rounded bg-indigo-100 px-2 py-0.5 text-[10px] font-medium text-indigo-800 dark:bg-indigo-950/50 dark:text-indigo-300">{{ $ht }}</span>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="flex flex-col items-center justify-center p-8">
                        <flux:icon name="arrow-path" class="h-6 w-6 animate-spin text-indigo-500" />
                        <p class="mt-2 text-xs text-zinc-500">{{ __('Generating high-retention SEO metadata...') }}</p>
                    </div>
                @endif

                <div class="mt-6 flex items-center justify-between border-t border-zinc-100 pt-4 dark:border-zinc-800">
                    <button
                        type="button"
                        wire:click="generateSeoMetadata(app(\App\Services\SeoGeneratorService::class))"
                        class="rounded-xl border border-zinc-200 px-3 py-1.5 text-xs font-semibold text-zinc-700 hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800"
                    >
                        {{ __('🔄 Re-generate SEO') }}
                    </button>
                    <button
                        type="button"
                        wire:click="closeSeoModal"
                        class="rounded-xl bg-zinc-900 px-4 py-2 text-xs font-bold text-white hover:bg-zinc-800 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-200"
                    >
                        {{ __('Done') }}
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Thumbnail Generator Modal -->
    @if ($showThumbnailModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm" x-data x-on:keydown.escape.window="$wire.closeThumbnailModal()">
            <div class="relative max-h-[90vh] w-full max-w-xl overflow-y-auto rounded-2xl border border-zinc-200 bg-white p-6 shadow-2xl dark:border-zinc-800 dark:bg-zinc-900 sm:p-7">
                <button
                    type="button"
                    wire:click="closeThumbnailModal"
                    class="absolute right-4 top-4 rounded-xl p-2 text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800"
                >
                    <flux:icon name="x-mark" class="h-5 w-5" />
                </button>

                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-950/40 dark:text-amber-400">
                        <flux:icon name="photo" class="h-6 w-6" />
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-zinc-900 dark:text-zinc-100">{{ __('High-CTR Thumbnail Generator') }}</h3>
                        <p class="text-xs text-zinc-500">{{ __('Applies niche color-grading filters, contrast boosts, and bold impact text banners.') }}</p>
                    </div>
                </div>

                <div class="mt-5 space-y-4 text-xs">
                    <!-- Overlay Text Input -->
                    <div>
                        <label class="block font-semibold text-zinc-700 dark:text-zinc-300 mb-1">
                            {{ __('Overlay Punch Text (2-5 words, e.g. "HE AWAKENED?!")') }}
                        </label>
                        <input
                            type="text"
                            wire:model="thumbnailText"
                            placeholder="e.g., HE AWAKENED?!"
                            class="w-full rounded-xl border border-zinc-300 bg-zinc-50 px-3.5 py-2 text-xs font-bold uppercase tracking-wider text-zinc-900 focus:border-amber-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100"
                        />
                    </div>

                    <!-- Niche Color Grading Preset -->
                    <div>
                        <label class="block font-semibold text-zinc-700 dark:text-zinc-300 mb-1">
                            {{ __('Color Grading Preset') }}
                        </label>
                        <select
                            wire:model="thumbnailNiche"
                            class="w-full rounded-xl border border-zinc-300 bg-zinc-50 px-3 py-2 text-xs text-zinc-900 focus:border-amber-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100"
                        >
                            <option value="manhwa_recap">{{ __('Manhwa High Contrast & Neon Pop') }}</option>
                            <option value="kids_story">{{ __('Kids Warm & Cheerful Vibrancy') }}</option>
                            <option value="science_explainer">{{ __('Science Deep Space & Cosmic Blue') }}</option>
                            <option value="horror_narration">{{ __('Horror Dark Vignette & Desaturated Cold') }}</option>
                            <option value="book_summary">{{ __('Book Minimalist Luxury & Gold Tone') }}</option>
                        </select>
                    </div>

                    <!-- Generated Thumbnail Preview -->
                    @if ($generatedThumbnailUrl)
                        <div class="space-y-2 pt-2">
                            <span class="font-semibold text-zinc-700 dark:text-zinc-300">{{ __('Rendered Thumbnail Result:') }}</span>
                            <div class="overflow-hidden rounded-xl border border-zinc-200 bg-black dark:border-zinc-700">
                                <img src="{{ $generatedThumbnailUrl }}" alt="Generated Thumbnail" class="w-full object-cover" />
                            </div>
                            <div class="flex justify-end">
                                <a
                                    href="{{ $generatedThumbnailUrl }}"
                                    download="thumbnail.jpg"
                                    target="_blank"
                                    class="inline-flex items-center gap-1.5 rounded-xl bg-amber-600 px-4 py-2 text-xs font-bold text-white hover:bg-amber-500"
                                >
                                    <flux:icon name="arrow-down-tray" class="h-4 w-4" />
                                    <span>{{ __('Download Thumbnail (JPEG)') }}</span>
                                </a>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="mt-6 flex items-center justify-between border-t border-zinc-100 pt-4 dark:border-zinc-800">
                    <button
                        type="button"
                        wire:click="closeThumbnailModal"
                        class="rounded-xl border border-zinc-200 px-4 py-2 text-xs font-semibold text-zinc-700 hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800"
                    >
                        {{ __('Close') }}
                    </button>
                    <button
                        type="button"
                        wire:click="generateThumbnail(app(\App\Services\ThumbnailGeneratorService::class))"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-gradient-to-r from-amber-600 to-amber-700 px-4 py-2 text-xs font-bold text-white shadow hover:from-amber-500 hover:to-amber-600"
                    >
                        <flux:icon name="sparkles" class="h-4 w-4" />
                        <span>{{ __('Render High-CTR Thumbnail') }}</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Clone to Language Modal -->
    @if ($showDuplicateModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm" x-data x-on:keydown.escape.window="$wire.closeDuplicateModal()">
            <div class="relative max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl border border-zinc-200 bg-white p-6 shadow-2xl dark:border-zinc-800 dark:bg-zinc-900 sm:p-7">
                <button
                    type="button"
                    wire:click="closeDuplicateModal"
                    class="absolute right-4 top-4 rounded-xl p-2 text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800"
                >
                    <flux:icon name="x-mark" class="h-5 w-5" />
                </button>

                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/40 dark:text-emerald-400">
                        <flux:icon name="globe-alt" class="h-6 w-6" />
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-zinc-900 dark:text-zinc-100">{{ __('Clone to Another Language') }}</h3>
                        <p class="text-xs text-zinc-500">{{ __('1 video → 15 global markets. Automatically translates scripts and synthesizes native voice.') }}</p>
                    </div>
                </div>

                <div class="mt-5 space-y-4 text-xs">
                    <!-- Target Language Selection -->
                    <div>
                        <label class="block font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">
                            {{ __('Select Target Language') }}
                        </label>
                        <select
                            wire:model="duplicateTargetLanguage"
                            class="w-full rounded-xl border border-zinc-300 bg-zinc-50 px-3 py-2.5 text-xs text-zinc-900 focus:border-emerald-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 font-medium"
                        >
                            @foreach ($supportedLanguages as $code => $label)
                                @if ($code !== 'auto')
                                    <option value="{{ $code }}">{{ $label }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>

                    <div class="rounded-xl border border-emerald-300/40 bg-emerald-50/60 p-3.5 text-emerald-900 dark:border-emerald-500/30 dark:bg-emerald-950/20 dark:text-emerald-200">
                        <div class="flex items-center gap-2 font-bold mb-1">
                            <flux:icon name="sparkles" class="h-4 w-4 text-emerald-600 dark:text-emerald-400" />
                            <span>{{ __('Zero Image Regeneration Cost') }}</span>
                        </div>
                        <p class="text-[11px] leading-relaxed text-zinc-600 dark:text-zinc-400">
                            {{ __('The cloned project reuses all comic artwork panels from this episode. Only text translations and native voice synthesis are performed.') }}
                        </p>
                    </div>
                </div>

                <div class="mt-6 flex items-center justify-between border-t border-zinc-100 pt-4 dark:border-zinc-800">
                    <button
                        type="button"
                        wire:click="closeDuplicateModal"
                        class="rounded-xl border border-zinc-200 px-4 py-2 text-xs font-semibold text-zinc-700 hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800"
                    >
                        {{ __('Cancel') }}
                    </button>
                    <button
                        type="button"
                        wire:click="duplicateToLanguage(app(\App\Services\TranslationService::class))"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 px-4 py-2 text-xs font-bold text-white shadow hover:from-emerald-500 hover:to-teal-500"
                    >
                        <flux:icon name="sparkles" class="h-4 w-4" />
                        <span>{{ __('Start Language Clone') }}</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    <script>
        function triggerSwalRegenerateAll() {
            if (typeof Swal === 'undefined') {
                if (confirm('🔄 Regenerate all scene assets (images & voiceovers)? Existing assets will be overwritten.')) {
                    @this.call('regenerateAllSceneAssets', 'both');
                }
                return;
            }

            Swal.fire({
                title: '🔄 Regenerate All Scene Assets?',
                html: `
                    <p class="text-xs text-zinc-400 mb-4 text-left leading-relaxed">
                        This will re-render all comic panel artwork and re-synthesize all voiceovers for every scene board in this episode. Existing assets will be overwritten.
                    </p>
                    <div class="text-left space-y-2 text-xs">
                        <p class="font-bold text-zinc-200">Select Regeneration Scope:</p>
                        <label class="flex items-center gap-2.5 cursor-pointer p-2.5 rounded-xl bg-zinc-800/90 border border-zinc-700 hover:border-purple-500 transition-all">
                            <input type="radio" name="swal_regen_scope" value="both" checked class="text-purple-600 focus:ring-purple-500">
                            <div>
                                <span class="block font-bold text-zinc-100">🔄 Re-render Both</span>
                                <span class="block text-[11px] text-zinc-400">Re-render all comic panel artwork + re-synthesize narrator audio</span>
                            </div>
                        </label>
                        <label class="flex items-center gap-2.5 cursor-pointer p-2.5 rounded-xl bg-zinc-800/90 border border-zinc-700 hover:border-blue-500 transition-all">
                            <input type="radio" name="swal_regen_scope" value="images_only" class="text-blue-600 focus:ring-blue-500">
                            <div>
                                <span class="block font-bold text-zinc-100">🎨 Images Only</span>
                                <span class="block text-[11px] text-zinc-400">Re-render comic visual panels with latest prompt engineering</span>
                            </div>
                        </label>
                        <label class="flex items-center gap-2.5 cursor-pointer p-2.5 rounded-xl bg-zinc-800/90 border border-zinc-700 hover:border-pink-500 transition-all">
                            <input type="radio" name="swal_regen_scope" value="voices_only" class="text-pink-600 focus:ring-pink-500">
                            <div>
                                <span class="block font-bold text-zinc-100">🎙️ Voices Only</span>
                                <span class="block text-[11px] text-zinc-400">Re-synthesize all dialogue lines with current voice narrator</span>
                            </div>
                        </label>
                    </div>
                `,
                icon: 'warning',
                iconColor: '#a855f7',
                showCancelButton: true,
                confirmButtonColor: '#9333ea',
                cancelButtonColor: '#3f3f46',
                confirmButtonText: '⚡ Yes, Regenerate Now',
                cancelButtonText: 'Cancel',
                background: '#18181b',
                color: '#f4f4f5',
                customClass: {
                    popup: 'rounded-2xl border border-zinc-700 shadow-2xl',
                    confirmButton: 'rounded-xl font-bold px-4 py-2.5 text-xs',
                    cancelButton: 'rounded-xl font-bold px-4 py-2.5 text-xs',
                },
                preConfirm: () => {
                    const selected = document.querySelector('input[name="swal_regen_scope"]:checked');
                    return selected ? selected.value : 'both';
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    @this.call('regenerateAllSceneAssets', result.value);
                }
            });
        }
    </script>
</div>
