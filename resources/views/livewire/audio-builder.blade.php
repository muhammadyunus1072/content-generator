<div class="w-full space-y-6">
    <!-- Header Banner -->
    <div class="relative overflow-hidden rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="absolute -right-16 -top-16 h-32 w-32 rounded-full bg-emerald-500/10 blur-3xl dark:bg-emerald-400/10"></div>
        <div class="absolute -bottom-16 -left-16 h-32 w-32 rounded-full bg-indigo-500/10 blur-3xl dark:bg-indigo-400/10"></div>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <flux:heading size="xl" level="1" class="bg-gradient-to-r from-emerald-600 via-teal-600 to-indigo-600 bg-clip-text text-transparent font-bold dark:from-emerald-400 dark:via-teal-400 dark:to-indigo-400">
                        {{ __('AI Audio Builder & Voice Studio') }}
                    </flux:heading>
                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-0.5 text-[11px] font-bold text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                        🎙️ {{ __('Neural Speech & Subtitles') }}
                    </span>
                </div>
                <flux:subheading>{{ __('Synthesize expressive voice narration with Edge-TTS or Local Docker (Kokoro/Cog), fine-tune pacing and pitch, and generate synchronized subtitle timestamps.') }}</flux:subheading>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('ai-config') }}" class="inline-flex items-center gap-1.5 rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-1.5 text-xs font-medium text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700 transition-colors">
                    <flux:icon name="cog" class="h-3.5 w-3.5" />
                    {{ __('TTS Transporters Config') }}
                </a>
            </div>
        </div>
    </div>

    <!-- Main Grid -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">

        <!-- Left Column: Input, Provider, Voice & Controls (7 cols) -->
        <div class="lg:col-span-7 space-y-6">

            <!-- Script Input Card -->
            <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 space-y-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <flux:icon name="document-text" class="h-4 w-4 text-emerald-600 dark:text-emerald-400" />
                        <h2 class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">{{ __('Script / Narration Text') }}</h2>
                    </div>

                    <!-- Quick Samples -->
                    <div class="flex items-center gap-1 text-xs">
                        <span class="text-zinc-500 text-[11px] mr-1">{{ __('Templates:') }}</span>
                        <button type="button" wire:click="applySample('documentary')" class="px-2 py-0.5 rounded bg-zinc-100 hover:bg-zinc-200 text-zinc-700 dark:bg-zinc-800 dark:hover:bg-zinc-700 dark:text-zinc-300 text-[11px] transition-colors">
                            📜 Documentary
                        </button>
                        <button type="button" wire:click="applySample('anime')" class="px-2 py-0.5 rounded bg-zinc-100 hover:bg-zinc-200 text-zinc-700 dark:bg-zinc-800 dark:hover:bg-zinc-700 dark:text-zinc-300 text-[11px] transition-colors">
                            ⚔️ Anime
                        </button>
                        <button type="button" wire:click="applySample('audiobook')" class="px-2 py-0.5 rounded bg-zinc-100 hover:bg-zinc-200 text-zinc-700 dark:bg-zinc-800 dark:hover:bg-zinc-700 dark:text-zinc-300 text-[11px] transition-colors">
                            📖 Audiobook
                        </button>
                    </div>
                </div>

                <div class="space-y-1.5">
                    <flux:textarea
                        wire:model="text"
                        rows="6"
                        placeholder="{{ __('Write or paste script for audio synthesis...') }}"
                        class="w-full font-sans text-sm"
                    />
                    <div class="flex items-center justify-between text-[11px] text-zinc-500">
                        <span>{{ Str::wordCount($text) }} {{ __('words') }} &bull; {{ strlen($text) }} {{ __('characters') }}</span>
                        <span>~{{ ceil(Str::wordCount($text) / 2.5) }} {{ __('seconds estimated duration') }}</span>
                    </div>
                </div>

                <!-- Custom Title -->
                <div>
                    <flux:input
                        wire:model="title"
                        placeholder="{{ __('Optional: Custom audio title (defaults to script summary)') }}"
                        size="sm"
                    />
                </div>
            </div>

            <!-- Provider Selection -->
            <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 space-y-4">
                <div class="flex items-center gap-2">
                    <flux:icon name="cpu-chip" class="h-4 w-4 text-emerald-600 dark:text-emerald-400" />
                    <h2 class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">{{ __('Speech Engine Provider') }}</h2>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <button
                        type="button"
                        wire:click="$set('provider', 'edge_tts')"
                        class="flex flex-col items-start p-3.5 rounded-xl border text-start transition-all cursor-pointer {{ $provider === 'edge_tts' ? 'border-emerald-500 bg-emerald-50/50 dark:bg-emerald-950/20 dark:border-emerald-500 ring-1 ring-emerald-500' : 'border-zinc-200 bg-zinc-50/50 hover:bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-800/50 dark:hover:bg-zinc-800' }}"
                    >
                        <div class="flex items-center justify-between w-full mb-1">
                            <span class="text-xs font-bold text-zinc-800 dark:text-zinc-200 flex items-center gap-1.5">
                                🌐 Edge-TTS (Python)
                            </span>
                            <span class="text-[10px] uppercase font-mono px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-300 font-semibold">
                                Free + Subtitles
                            </span>
                        </div>
                        <p class="text-[11px] text-zinc-500 dark:text-zinc-400 leading-relaxed">
                            Zero-cost neural voices with word-level WebVTT subtitles and emotional inflections.
                        </p>
                    </button>

                    <button
                        type="button"
                        wire:click="$set('provider', 'docker_tts')"
                        class="flex flex-col items-start p-3.5 rounded-xl border text-start transition-all cursor-pointer {{ $provider === 'docker_tts' ? 'border-indigo-500 bg-indigo-50/50 dark:bg-indigo-950/20 dark:border-indigo-500 ring-1 ring-indigo-500' : 'border-zinc-200 bg-zinc-50/50 hover:bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-800/50 dark:hover:bg-zinc-800' }}"
                    >
                        <div class="flex items-center justify-between w-full mb-1">
                            <span class="text-xs font-bold text-zinc-800 dark:text-zinc-200 flex items-center gap-1.5">
                                🐳 Local Docker TTS
                            </span>
                            <span class="text-[10px] uppercase font-mono px-1.5 py-0.5 rounded bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-300 font-semibold">
                                Offline GPU / CPU
                            </span>
                        </div>
                        <p class="text-[11px] text-zinc-500 dark:text-zinc-400 leading-relaxed">
                            Local container synthesis (Kokoro-82M or Cog prediction server) on RTX 3050.
                        </p>
                    </button>
                </div>

                @if($provider === 'docker_tts')
                    <div class="p-3.5 rounded-xl border border-indigo-200 bg-indigo-50/40 dark:border-indigo-800 dark:bg-indigo-950/20 space-y-3">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                            <label class="text-xs font-medium text-zinc-700 dark:text-zinc-300">{{ __('Docker API Protocol:') }}</label>
                            <div class="flex items-center gap-2">
                                <label class="inline-flex items-center gap-1.5 text-xs text-zinc-700 dark:text-zinc-300 cursor-pointer">
                                    <input type="radio" wire:model.live="dockerProtocol" value="openai" class="text-indigo-600 focus:ring-indigo-500">
                                    <span>OpenAI /v1/audio/speech (Kokoro)</span>
                                </label>
                                <label class="inline-flex items-center gap-1.5 text-xs text-zinc-700 dark:text-zinc-300 cursor-pointer">
                                    <input type="radio" wire:model.live="dockerProtocol" value="cog" class="text-indigo-600 focus:ring-indigo-500">
                                    <span>Replicate Cog (/predictions)</span>
                                </label>
                            </div>
                        </div>

                        <div>
                            <flux:input
                                wire:model="dockerBaseUrl"
                                label="{{ __('Endpoint Base URL') }}"
                                placeholder="http://localhost:8880"
                                size="sm"
                            />
                            <p class="text-[10px] text-zinc-500 mt-1">
                                Default for Kokoro-FastAPI is <code>http://localhost:8880</code>. Cog containers default to <code>http://localhost:5000</code>.
                            </p>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Voice Selection Card -->
            <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                    <div class="flex items-center gap-2">
                        <flux:icon name="user" class="h-4 w-4 text-emerald-600 dark:text-emerald-400" />
                        <h2 class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">{{ __('Voice Selection & Personas') }}</h2>
                    </div>

                    <!-- Voice Search -->
                    <div class="w-full sm:w-48">
                        <flux:input
                            wire:model.live.debounce.250ms="voiceSearch"
                            placeholder="{{ __('Search voice...') }}"
                            size="sm"
                            icon="magnifying-glass"
                        />
                    </div>
                </div>

                <!-- Category Filter Tabs -->
                <div class="flex flex-wrap gap-1 border-b border-zinc-200 dark:border-zinc-700 pb-2">
                    <button
                        type="button"
                        wire:click="setVoiceCategory('all')"
                        class="px-2.5 py-1 rounded-lg text-xs font-medium transition-colors {{ $activeVoiceCategory === 'all' ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900' : 'text-zinc-600 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800' }}"
                    >
                        {{ __('All Voices') }}
                    </button>
                    @if($provider === 'edge_tts')
                        <button
                            type="button"
                            wire:click="setVoiceCategory('Indonesian & Nusantara')"
                            class="px-2.5 py-1 rounded-lg text-xs font-medium transition-colors {{ $activeVoiceCategory === 'Indonesian & Nusantara' ? 'bg-emerald-600 text-white' : 'text-zinc-600 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800' }}"
                        >
                            🇮🇩 Indonesian & Nusantara
                        </button>
                        <button
                            type="button"
                            wire:click="setVoiceCategory('English International')"
                            class="px-2.5 py-1 rounded-lg text-xs font-medium transition-colors {{ $activeVoiceCategory === 'English International' ? 'bg-blue-600 text-white' : 'text-zinc-600 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800' }}"
                        >
                            🇬🇧 English International
                        </button>
                        <button
                            type="button"
                            wire:click="setVoiceCategory('Anime & Japanese')"
                            class="px-2.5 py-1 rounded-lg text-xs font-medium transition-colors {{ $activeVoiceCategory === 'Anime & Japanese' ? 'bg-pink-600 text-white' : 'text-zinc-600 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800' }}"
                        >
                            🇯🇵 Anime & Japanese
                        </button>
                    @else
                        <button
                            type="button"
                            wire:click="setVoiceCategory('Docker Kokoro-82M')"
                            class="px-2.5 py-1 rounded-lg text-xs font-medium transition-colors {{ $activeVoiceCategory === 'Docker Kokoro-82M' ? 'bg-indigo-600 text-white' : 'text-zinc-600 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800' }}"
                        >
                            ⚡ Kokoro-82M Voices
                        </button>
                    @endif
                </div>

                <!-- Active Voice Banner -->
                <div class="flex items-center justify-between p-3 rounded-xl border border-emerald-200 bg-emerald-50/50 dark:border-emerald-800 dark:bg-emerald-950/30">
                    <div class="flex items-center gap-2.5">
                        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-600 text-white font-bold text-sm">
                            {{ substr($voice, 0, 2) }}
                        </div>
                        <div>
                            <div class="flex items-center gap-1.5">
                                <span class="text-xs font-bold text-zinc-900 dark:text-zinc-100">{{ $voice }}</span>
                                <span class="rounded bg-emerald-200/60 dark:bg-emerald-800/60 px-1.5 py-0.2 text-[10px] font-semibold text-emerald-800 dark:text-emerald-200">{{ __('Selected') }}</span>
                            </div>
                            <span class="text-[11px] text-zinc-600 dark:text-zinc-400">
                                {{ $this->resolveVoiceName($voice) }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Voices Scrollable Grid -->
                <div class="max-h-56 overflow-y-auto pr-1 space-y-2">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        @forelse($filteredVoices as $v)
                            <div
                                wire:click="setVoice('{{ $v['key'] }}')"
                                class="p-2.5 rounded-xl border text-start transition-all cursor-pointer {{ $voice === $v['key'] ? 'border-emerald-500 bg-emerald-50/60 dark:bg-emerald-950/40 ring-1 ring-emerald-500' : 'border-zinc-200 bg-zinc-50/40 hover:bg-zinc-100 dark:border-zinc-800 dark:bg-zinc-800/40 dark:hover:bg-zinc-800' }}"
                            >
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-xs font-semibold text-zinc-800 dark:text-zinc-200 truncate">
                                        {{ $v['name'] }}
                                    </span>
                                    <span class="text-[10px] px-1.5 py-0.2 rounded font-medium {{ ($v['gender'] ?? '') === 'Female' ? 'bg-pink-100 text-pink-700 dark:bg-pink-950 dark:text-pink-300' : 'bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-300' }}">
                                        {{ $v['gender'] ?? 'Voice' }}
                                    </span>
                                </div>
                                <p class="text-[10px] text-zinc-500 line-clamp-1">
                                    {{ $v['personality'] ?? ($v['language'] ?? $v['key']) }}
                                </p>
                            </div>
                        @empty
                            <div class="col-span-2 py-4 text-center text-xs text-zinc-400">
                                {{ __('No voices matching your filter.') }}
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- Custom Voice Override -->
                <div>
                    <flux:input
                        wire:model="customVoice"
                        label="{{ __('Custom Voice Override (Optional)') }}"
                        placeholder="{{ __('e.g. af_heart, custom voice ID or weights path') }}"
                        size="sm"
                    />
                </div>
            </div>

            <!-- Pacing & Voice Controls -->
            <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 space-y-4">
                <div class="flex items-center gap-2">
                    <flux:icon name="adjustments-horizontal" class="h-4 w-4 text-emerald-600 dark:text-emerald-400" />
                    <h2 class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">{{ __('Speech Pacing & Tuning') }}</h2>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Speech Rate -->
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between text-xs">
                            <label class="font-medium text-zinc-700 dark:text-zinc-300">{{ __('Speed / Rate') }}</label>
                            <span class="font-mono text-emerald-600 dark:text-emerald-400 font-semibold">{{ $rate }}</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <button type="button" wire:click="$set('rate', '-20%')" class="px-2 py-1 rounded text-[11px] bg-zinc-100 dark:bg-zinc-800 hover:bg-zinc-200 text-zinc-700 dark:text-zinc-300">-20% (Slow)</button>
                            <button type="button" wire:click="$set('rate', '-15%')" class="px-2 py-1 rounded text-[11px] bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-200 font-semibold">-15% (Story)</button>
                            <button type="button" wire:click="$set('rate', '+0%')" class="px-2 py-1 rounded text-[11px] bg-zinc-100 dark:bg-zinc-800 hover:bg-zinc-200 text-zinc-700 dark:text-zinc-300">0% (Normal)</button>
                            <button type="button" wire:click="$set('rate', '+20%')" class="px-2 py-1 rounded text-[11px] bg-zinc-100 dark:bg-zinc-800 hover:bg-zinc-200 text-zinc-700 dark:text-zinc-300">+20% (Fast)</button>
                        </div>
                    </div>

                    <!-- Pitch Adjustment -->
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between text-xs">
                            <label class="font-medium text-zinc-700 dark:text-zinc-300">{{ __('Voice Pitch') }}</label>
                            <span class="font-mono text-indigo-600 dark:text-indigo-400 font-semibold">{{ $pitch }}</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <button type="button" wire:click="$set('pitch', '-10Hz')" class="px-2 py-1 rounded text-[11px] bg-indigo-100 dark:bg-indigo-950 text-indigo-800 dark:text-indigo-200 font-semibold">-10Hz (Deep)</button>
                            <button type="button" wire:click="$set('pitch', '-5Hz')" class="px-2 py-1 rounded text-[11px] bg-zinc-100 dark:bg-zinc-800 hover:bg-zinc-200 text-zinc-700 dark:text-zinc-300">-5Hz</button>
                            <button type="button" wire:click="$set('pitch', '+0Hz')" class="px-2 py-1 rounded text-[11px] bg-zinc-100 dark:bg-zinc-800 hover:bg-zinc-200 text-zinc-700 dark:text-zinc-300">0Hz (Natural)</button>
                            <button type="button" wire:click="$set('pitch', '+10Hz')" class="px-2 py-1 rounded text-[11px] bg-zinc-100 dark:bg-zinc-800 hover:bg-zinc-200 text-zinc-700 dark:text-zinc-300">+10Hz (High)</button>
                        </div>
                    </div>
                </div>

                <!-- Dramatic Pause Toggle -->
                <div class="pt-2 border-t border-zinc-100 dark:border-zinc-800 flex items-center justify-between">
                    <div>
                        <span class="text-xs font-semibold text-zinc-800 dark:text-zinc-200 block">{{ __('Cinematic Storyteller Pauses') }}</span>
                        <span class="text-[11px] text-zinc-500">{{ __('Automatically converts sentence periods into breathing pauses for immersive narration.') }}</span>
                    </div>
                    <flux:switch wire:model="applyDramaticPause" />
                </div>
            </div>

            <!-- Error Notification -->
            @if($errorMessage)
                <div class="p-3.5 rounded-xl border border-red-200 bg-red-50 dark:border-red-800 dark:bg-red-950/40 text-xs text-red-700 dark:text-red-300">
                    <strong>{{ __('Error:') }}</strong> {{ $errorMessage }}
                </div>
            @endif

            <!-- Generate Action Button -->
            <div>
                <flux:button
                    wire:click="generateAudio"
                    variant="primary"
                    class="w-full py-3 bg-gradient-to-r from-emerald-600 via-teal-600 to-indigo-600 text-white font-bold shadow-md hover:opacity-95"
                    :disabled="$isGenerating"
                >
                    <span wire:loading.remove wire:target="generateAudio" class="flex items-center gap-2">
                        <flux:icon name="speaker-wave" class="h-5 w-5" />
                        {{ __('⚡ Generate Voice Narration & Subtitles') }}
                    </span>
                    <span wire:loading wire:target="generateAudio" class="flex items-center gap-2">
                        <svg class="animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        {{ __('Synthesizing Speech...') }}
                    </span>
                </flux:button>
            </div>
        </div>

        <!-- Right Column: Player, Synchronized Subtitles & History (5 cols) -->
        <div class="lg:col-span-5 space-y-6">

            <!-- Active Audio Player & Subtitle Cue Card -->
            <div
                x-data="{
                    currentTime: 0,
                    activeCueIndex: null,
                    seekTo(sec) {
                        const audio = document.getElementById('audio-builder-player');
                        if (audio) {
                            audio.currentTime = sec;
                            audio.play();
                        }
                    },
                    updateTime(time) {
                        this.currentTime = time;
                    }
                }"
                class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 space-y-4"
            >
                <div class="flex items-center justify-between border-b border-zinc-100 dark:border-zinc-800 pb-3">
                    <div class="flex items-center gap-2">
                        <div class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></div>
                        <h2 class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">{{ __('Audio Studio Player') }}</h2>
                    </div>

                    @if($currentAudio)
                        <span class="rounded bg-zinc-100 dark:bg-zinc-800 px-2 py-0.5 text-[11px] font-mono text-zinc-600 dark:text-zinc-400">
                            {{ $currentAudio->formatted_duration }}
                        </span>
                    @endif
                </div>

                @if($currentAudio)
                    <!-- Metadata Header -->
                    <div>
                        <h3 class="text-sm font-bold text-zinc-900 dark:text-zinc-100 truncate">
                            {{ $currentAudio->title }}
                        </h3>
                        <div class="flex flex-wrap items-center gap-1.5 mt-1">
                            <span class="rounded bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300 px-1.5 py-0.2 text-[10px] font-semibold">
                                {{ $currentAudio->voice_name }}
                            </span>
                            <span class="rounded bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300 px-1.5 py-0.2 text-[10px] font-mono">
                                {{ strtoupper($currentAudio->provider) }}
                            </span>
                            @if($currentAudio->file_size_bytes)
                                <span class="text-[10px] text-zinc-500">
                                    {{ round($currentAudio->file_size_bytes / 1024) }} KB
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- Native HTML5 Audio Element with Alpine Hook -->
                    <div class="bg-zinc-50 dark:bg-zinc-800/60 p-3 rounded-xl border border-zinc-200 dark:border-zinc-700">
                        <audio
                            id="audio-builder-player"
                            controls
                            class="w-full"
                            src="{{ $currentAudio->audio_url }}"
                            @timeupdate="updateTime($el.currentTime)"
                        >
                            {{ __('Your browser does not support audio playback.') }}
                        </audio>
                    </div>

                    <!-- Export Toolbar -->
                    <div class="flex flex-wrap items-center gap-1.5 pt-1">
                        <a
                            href="{{ $currentAudio->audio_url }}"
                            download
                            class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 hover:bg-zinc-50 text-xs font-semibold text-zinc-700 dark:text-zinc-200 transition-colors"
                        >
                            <flux:icon name="arrow-down-tray" class="h-3.5 w-3.5 text-emerald-600" />
                            {{ __('Audio (.mp3)') }}
                        </a>

                        @if($currentAudio->subtitle_path)
                            <button
                                type="button"
                                wire:click="downloadVtt({{ $currentAudio->id }})"
                                class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 hover:bg-zinc-50 text-xs font-semibold text-zinc-700 dark:text-zinc-200 transition-colors"
                            >
                                <flux:icon name="document-text" class="h-3.5 w-3.5 text-blue-600" />
                                {{ __('Subtitles (.vtt)') }}
                            </button>

                            <button
                                type="button"
                                wire:click="downloadSrt({{ $currentAudio->id }})"
                                class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 hover:bg-zinc-50 text-xs font-semibold text-zinc-700 dark:text-zinc-200 transition-colors"
                            >
                                <flux:icon name="document-duplicate" class="h-3.5 w-3.5 text-indigo-600" />
                                {{ __('Subtitles (.srt)') }}
                            </button>
                        @endif
                    </div>

                    <!-- Synchronized Subtitle Timeline -->
                    <div class="space-y-2 pt-2 border-t border-zinc-100 dark:border-zinc-800">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-zinc-700 dark:text-zinc-300 flex items-center gap-1">
                                <flux:icon name="clock" class="h-3.5 w-3.5 text-emerald-600" />
                                {{ __('Synchronized Subtitle Timeline') }}
                            </span>
                            <span class="text-[10px] text-zinc-400">
                                {{ __('Click cue to seek audio') }}
                            </span>
                        </div>

                        @if(!empty($currentAudio->subtitle_cues))
                            <div class="max-h-64 overflow-y-auto space-y-1.5 pr-1">
                                @foreach($currentAudio->subtitle_cues as $cue)
                                    <div
                                        @click="seekTo({{ $cue['start'] }})"
                                        :class="currentTime >= {{ $cue['start'] }} && currentTime <= {{ $cue['end'] }}
                                            ? 'border-emerald-500 bg-emerald-50/80 dark:bg-emerald-950/60 ring-1 ring-emerald-500 text-emerald-900 dark:text-emerald-100'
                                            : 'border-zinc-200 bg-zinc-50/50 hover:bg-zinc-100 dark:border-zinc-800 dark:bg-zinc-800/40 dark:hover:bg-zinc-800 text-zinc-700 dark:text-zinc-300'"
                                        class="p-2.5 rounded-xl border text-start cursor-pointer transition-all flex flex-col gap-1 text-xs"
                                    >
                                        <div class="flex items-center justify-between text-[10px] font-mono text-zinc-500 dark:text-zinc-400">
                                            <span>#{{ $cue['index'] ?? $loop->iteration }} &bull; {{ $cue['start_formatted'] ?? '' }} &rarr; {{ $cue['end_formatted'] ?? '' }}</span>
                                            <span x-show="currentTime >= {{ $cue['start'] }} && currentTime <= {{ $cue['end'] }}" class="text-emerald-600 font-bold uppercase tracking-wider text-[9px]">
                                                ● Playing
                                            </span>
                                        </div>
                                        <p class="leading-relaxed font-sans text-xs">
                                            {{ $cue['text'] }}
                                        </p>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="p-4 rounded-xl border border-dashed border-zinc-200 dark:border-zinc-800 text-center text-xs text-zinc-400">
                                {{ __('No subtitle cues available for this audio file (available when using Edge-TTS).') }}
                            </div>
                        @endif
                    </div>
                @else
                    <div class="py-12 text-center text-zinc-400 space-y-2">
                        <flux:icon name="speaker-wave" class="h-10 w-10 mx-auto text-zinc-300 dark:text-zinc-600" />
                        <p class="text-xs">{{ __('No audio generated yet. Write script and click Generate.') }}</p>
                    </div>
                @endif
            </div>

            <!-- Generation History -->
            <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 space-y-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <flux:icon name="archive-box" class="h-4 w-4 text-emerald-600 dark:text-emerald-400" />
                        <h2 class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">{{ __('Audio Library History') }}</h2>
                    </div>
                    <span class="text-xs text-zinc-400">{{ $history->total() }} {{ __('clips') }}</span>
                </div>

                <div class="space-y-2">
                    @forelse($history as $item)
                        <div class="flex items-center justify-between p-2.5 rounded-xl border border-zinc-100 hover:border-zinc-200 bg-zinc-50/50 hover:bg-zinc-100 dark:border-zinc-800 dark:bg-zinc-800/30 dark:hover:bg-zinc-800 transition-all text-xs">
                            <div class="flex items-center gap-2.5 min-w-0 pr-2">
                                <button
                                    type="button"
                                    wire:click="loadAudio({{ $item->id }})"
                                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $currentAudioId === $item->id ? 'bg-emerald-600 text-white' : 'bg-zinc-200 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-200' }} hover:scale-105 transition-transform"
                                >
                                    <flux:icon name="play" class="h-4 w-4" />
                                </button>
                                <div class="min-w-0">
                                    <h4 class="font-semibold text-zinc-900 dark:text-zinc-100 truncate text-xs">{{ $item->title }}</h4>
                                    <div class="flex items-center gap-2 text-[10px] text-zinc-500">
                                        <span>{{ $item->formatted_duration }}</span>
                                        <span>&bull;</span>
                                        <span>{{ $item->voice_name }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center gap-1 shrink-0">
                                <a href="{{ $item->audio_url }}" download class="p-1.5 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200">
                                    <flux:icon name="arrow-down-tray" class="h-4 w-4" />
                                </a>
                                <button type="button" wire:click="deleteAudio({{ $item->id }})" wire:confirm="Delete this audio generation?" class="p-1.5 text-zinc-400 hover:text-red-600">
                                    <flux:icon name="trash" class="h-4 w-4" />
                                </button>
                            </div>
                        </div>
                    @empty
                        <div class="py-6 text-center text-xs text-zinc-400">
                            {{ __('History is empty.') }}
                        </div>
                    @endforelse
                </div>

                @if($history->hasPages())
                    <div class="pt-2 border-t border-zinc-100 dark:border-zinc-800">
                        {{ $history->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>
</div>
