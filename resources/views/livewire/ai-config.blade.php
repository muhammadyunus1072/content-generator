<div class="w-full space-y-6">
    <!-- Header Banner -->
    <div class="relative overflow-hidden rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="absolute -right-16 -top-16 h-32 w-32 rounded-full bg-emerald-500/10 blur-3xl dark:bg-emerald-400/10"></div>
        <div class="absolute -bottom-16 -left-16 h-32 w-32 rounded-full bg-blue-500/10 blur-3xl dark:bg-blue-400/10"></div>
        
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <flux:heading size="xl" level="1" class="mb-1 bg-gradient-to-r from-emerald-600 via-teal-600 to-blue-600 bg-clip-text text-transparent font-bold dark:from-emerald-400 dark:to-blue-400">
                    {{ __('AI Configuration & Transporters') }}
                </flux:heading>
                <flux:subheading>{{ __('Manage API keys, custom endpoints, and dynamic model routing across all generation features.') }}</flux:subheading>
            </div>

            <button 
                wire:click="openNewModal"
                class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 px-4 py-2 text-xs font-bold text-white shadow-sm hover:from-emerald-700 hover:to-teal-700 transition-all"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                {{ __('Add Custom AI Model') }}
            </button>
        </div>

        <!-- Modality Tabs -->
        <div class="mt-6 flex items-center gap-2 border-t border-zinc-100 pt-4 dark:border-zinc-800 overflow-x-auto">
            <button 
                wire:click="setCategory('text')"
                class="flex items-center gap-2 rounded-lg px-4 py-2 text-xs font-bold transition-all {{ $activeCategory === 'text' ? 'bg-emerald-600 text-white shadow-sm' : 'bg-zinc-100 text-zinc-600 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-400' }}"
            >
                💬 {{ __('Text & Script Models') }}
            </button>
            <button 
                wire:click="setCategory('image')"
                class="flex items-center gap-2 rounded-lg px-4 py-2 text-xs font-bold transition-all {{ $activeCategory === 'image' ? 'bg-blue-600 text-white shadow-sm' : 'bg-zinc-100 text-zinc-600 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-400' }}"
            >
                🎨 {{ __('Image Generation Models') }}
            </button>
            <button 
                wire:click="setCategory('audio_tts')"
                class="flex items-center gap-2 rounded-lg px-4 py-2 text-xs font-bold transition-all {{ $activeCategory === 'audio_tts' ? 'bg-purple-600 text-white shadow-sm' : 'bg-zinc-100 text-zinc-600 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-400' }}"
            >
                🎙️ {{ __('Voiceover (TTS)') }}
            </button>
            <button 
                wire:click="setCategory('audio_stt')"
                class="flex items-center gap-2 rounded-lg px-4 py-2 text-xs font-bold transition-all {{ $activeCategory === 'audio_stt' ? 'bg-amber-600 text-white shadow-sm' : 'bg-zinc-100 text-zinc-600 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-400' }}"
            >
                📝 {{ __('Transcription (STT)') }}
            </button>
        </div>
    </div>

    <!-- Quick Preset Templates Bar -->
    <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex items-center gap-2 mb-2">
            <span class="text-xs font-bold uppercase tracking-wider text-zinc-500">{{ __('1-Click Quick Presets:') }}</span>
        </div>
        <div class="flex flex-wrap gap-2">
            @foreach ($presets as $preset)
                <button 
                    wire:click="applyPreset('{{ $preset['name'] }}')"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-1.5 text-xs font-medium text-zinc-700 hover:border-emerald-400 hover:bg-emerald-50/50 hover:text-emerald-700 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:border-emerald-500 dark:hover:text-emerald-300 transition-all"
                >
                    <span class="text-emerald-500 font-bold">+</span>
                    {{ $preset['name'] }}
                </button>
            @endforeach
        </div>
    </div>

    <!-- Active Connections Grid -->
    <div class="space-y-4">
        <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">
            {{ __('Configured Transporters & Models') }} ({{ $connections->count() }})
        </h3>

        @if ($connections->isEmpty())
            <div class="flex flex-col items-center justify-center rounded-xl border border-dashed border-zinc-300 p-12 text-center dark:border-zinc-700">
                <span class="text-3xl">🔌</span>
                <h4 class="mt-2 text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ __('No models configured in this category') }}</h4>
                <p class="mt-1 text-xs text-zinc-500">{{ __('Click one of the Quick Presets above or add a custom model connection.') }}</p>
            </div>
        @else
            <div class="grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($connections as $conn)
                    <div class="flex flex-col justify-between rounded-xl border {{ $conn->is_default ? 'border-emerald-400 bg-emerald-50/10 shadow-sm dark:border-emerald-500 dark:bg-emerald-950/10' : 'border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900' }} p-5 transition-all">
                        <div class="space-y-3">
                            <!-- Top Card Header -->
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <h4 class="font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-1.5">
                                        {{ $conn->name }}
                                        @if ($conn->is_default)
                                            <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300">
                                                ★ {{ __('Default') }}
                                            </span>
                                        @endif
                                    </h4>
                                    <span class="text-[11px] font-mono text-zinc-400">{{ $conn->model_id }}</span>
                                </div>

                                <button 
                                    wire:click="toggleActive({{ $conn->id }})" 
                                    class="rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $conn->is_active ? 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300' : 'bg-red-50 text-red-600 dark:bg-red-950/50 dark:text-red-400' }}"
                                >
                                    {{ $conn->is_active ? __('Active') : __('Disabled') }}
                                </button>
                            </div>

                            <!-- Connection Details -->
                            <div class="space-y-1.5 rounded-lg bg-zinc-50 p-3 text-xs dark:bg-zinc-800/60">
                                <div class="flex items-center justify-between">
                                    <span class="text-zinc-400">{{ __('Provider:') }}</span>
                                    <span class="font-semibold text-zinc-700 dark:text-zinc-300 uppercase">{{ $conn->provider }}</span>
                                </div>
                                @if ($conn->base_url)
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="text-zinc-400 shrink-0">{{ __('Endpoint:') }}</span>
                                        <span class="font-mono text-[11px] text-zinc-600 dark:text-zinc-400 truncate">{{ $conn->base_url }}</span>
                                    </div>
                                @endif
                                @if ($conn->masked_api_key)
                                    <div class="flex items-center justify-between">
                                        <span class="text-zinc-400">{{ __('API Key:') }}</span>
                                        <span class="font-mono text-[11px] text-zinc-600 dark:text-zinc-400">{{ $conn->masked_api_key }}</span>
                                    </div>
                                @endif
                            </div>

                            <!-- Live Test Status Feedback -->
                            @if ($conn->last_tested_at)
                                <div class="flex items-center gap-1.5 text-[11px] {{ $conn->last_test_status === 'success' ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-500' }}">
                                    <span>{{ $conn->last_test_status === 'success' ? '🟢' : '🔴' }}</span>
                                    <span class="truncate">{{ $conn->last_test_message }}</span>
                                </div>
                            @endif
                        </div>

                        <!-- Card Action Buttons -->
                        <div class="mt-4 flex items-center justify-between border-t border-zinc-100 pt-3 dark:border-zinc-800 text-xs">
                            <div class="flex items-center gap-2">
                                <button 
                                    wire:click="testConnection({{ $conn->id }})" 
                                    wire:loading.attr="disabled"
                                    class="inline-flex items-center gap-1 font-semibold text-emerald-600 hover:text-emerald-700 dark:text-emerald-400"
                                >
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                    </svg>
                                    {{ __('Test') }}
                                </button>

                                @if (in_array($conn->provider, ['docker_cog', 'docker_replicate']))
                                    <button 
                                        wire:click="warmupConnection({{ $conn->id }})" 
                                        wire:loading.attr="disabled"
                                        class="inline-flex items-center gap-1 font-semibold text-amber-600 hover:text-amber-700 dark:text-amber-400"
                                        title="{{ __('Run a 1-step test to load CUDA weights into GPU memory') }}"
                                    >
                                        ⚡ {{ __('Warmup GPU') }}
                                    </button>
                                @endif

                                @if (! $conn->is_default)
                                    <button 
                                        wire:click="makeDefault({{ $conn->id }})" 
                                        class="text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200 font-medium"
                                    >
                                        {{ __('Set Default') }}
                                    </button>
                                @endif
                            </div>

                            <div class="flex items-center gap-2">
                                <button wire:click="editConnection({{ $conn->id }})" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200">
                                    {{ __('Edit') }}
                                </button>
                                <button wire:click="deleteConnection({{ $conn->id }})" wire:confirm="{{ __('Delete this model connection?') }}" class="text-red-400 hover:text-red-600">
                                    {{ __('Delete') }}
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- VOICE NARRATOR SHOWCASE & AUDITION CATALOG (Rendered in audio_tts tab) -->
    @if ($activeCategory === 'audio_tts')
        <div class="mt-8 space-y-5 rounded-2xl border border-purple-200 bg-purple-50/20 p-6 shadow-sm dark:border-purple-900/50 dark:bg-purple-950/10">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-lg font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                        <span>🎧</span> {{ __('Voice Narrator Catalog & Live Audition') }}
                    </h3>
                    <p class="text-xs text-zinc-500">
                        {{ __('Audition each narrator directly in your browser. All voices speak the standard verification sample text.') }}
                    </p>
                </div>
            </div>

            <!-- Sample Text Banner -->
            <div class="rounded-xl border border-purple-200 bg-white p-3.5 text-xs text-zinc-700 shadow-sm dark:border-purple-900 dark:bg-zinc-900 dark:text-zinc-300">
                <span class="font-bold text-purple-600 dark:text-purple-400 uppercase tracking-wider text-[10px] block mb-1">
                    {{ __('Audition Sample Text:') }}
                </span>
                <p class="italic font-serif leading-relaxed">
                    "Proses ekstraksi dan verifikasi dokumen telah berhasil diselesaikan dengan tingkat akurasi 99,8 persen. Algoritma secara otomatis mengidentifikasi anomali data sebelum diunggah ke server utama."
                </p>
            </div>

            <!-- Category Filter & Search Bar -->
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between border-t border-purple-100 pt-4 dark:border-purple-900/40">
                <div class="flex flex-wrap items-center gap-1.5">
                    <button 
                        wire:click="setVoiceCategoryFilter('all')"
                        class="rounded-lg px-3 py-1 text-xs font-semibold transition-all {{ $voiceCategoryFilter === 'all' ? 'bg-purple-600 text-white shadow-sm' : 'bg-white text-zinc-600 hover:bg-zinc-100 dark:bg-zinc-800 dark:text-zinc-300' }}"
                    >
                        {{ __('All Voices') }}
                    </button>
                    <button 
                        wire:click="setVoiceCategoryFilter('Indonesian & Nusantara')"
                        class="rounded-lg px-3 py-1 text-xs font-semibold transition-all {{ $voiceCategoryFilter === 'Indonesian & Nusantara' ? 'bg-purple-600 text-white shadow-sm' : 'bg-white text-zinc-600 hover:bg-zinc-100 dark:bg-zinc-800 dark:text-zinc-300' }}"
                    >
                        🇮🇩 {{ __('Indonesia & Nusantara') }}
                    </button>
                    <button 
                        wire:click="setVoiceCategoryFilter('English & Multilingual')"
                        class="rounded-lg px-3 py-1 text-xs font-semibold transition-all {{ $voiceCategoryFilter === 'English & Multilingual' ? 'bg-purple-600 text-white shadow-sm' : 'bg-white text-zinc-600 hover:bg-zinc-100 dark:bg-zinc-800 dark:text-zinc-300' }}"
                    >
                        🌐 {{ __('English Storytellers') }}
                    </button>
                    <button 
                        wire:click="setVoiceCategoryFilter('Anime & Asian Media')"
                        class="rounded-lg px-3 py-1 text-xs font-semibold transition-all {{ $voiceCategoryFilter === 'Anime & Asian Media' ? 'bg-purple-600 text-white shadow-sm' : 'bg-white text-zinc-600 hover:bg-zinc-100 dark:bg-zinc-800 dark:text-zinc-300' }}"
                    >
                        🇯🇵 🇰🇷 {{ __('Anime & Manhwa') }}
                    </button>
                    <button 
                        wire:click="setVoiceCategoryFilter('European & Global')"
                        class="rounded-lg px-3 py-1 text-xs font-semibold transition-all {{ $voiceCategoryFilter === 'European & Global' ? 'bg-purple-600 text-white shadow-sm' : 'bg-white text-zinc-600 hover:bg-zinc-100 dark:bg-zinc-800 dark:text-zinc-300' }}"
                    >
                        🌍 {{ __('European & Global') }}
                    </button>
                </div>

                <div class="w-full sm:w-64">
                    <input 
                        type="text" 
                        wire:model.live.debounce.250ms="voiceSearch"
                        placeholder="{{ __('Search voice, language, tone...') }}"
                        class="w-full rounded-lg border border-zinc-200 bg-white px-3 py-1.5 text-xs text-zinc-900 shadow-sm focus:border-purple-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100"
                    >
                </div>
            </div>

            <!-- Voice Grid with Live Audio Player -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @forelse ($voiceCatalog as $voice)
                    @php
                        $isCurrentDefault = ($defaultTtsConnection && $defaultTtsConnection->model_id === $voice['key']);
                    @endphp
                    <div class="flex flex-col justify-between rounded-xl border {{ $isCurrentDefault ? 'border-purple-500 bg-purple-50/50 shadow-md dark:border-purple-500 dark:bg-purple-950/30' : 'border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900' }} p-4 transition-all hover:border-purple-300">
                        <div class="space-y-2.5">
                            <!-- Voice Header -->
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <h4 class="font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-1.5 text-sm">
                                        {{ $voice['name'] }}
                                        @if ($isCurrentDefault)
                                            <span class="rounded-full bg-purple-600 px-2 py-0.5 text-[10px] font-bold text-white">
                                                ★ {{ __('Active Default') }}
                                            </span>
                                        @endif
                                    </h4>
                                    <span class="text-[11px] text-zinc-400 font-mono">{{ $voice['key'] }}</span>
                                </div>
                                <span class="rounded-full px-2 py-0.5 text-[10px] font-bold {{ $voice['gender'] === 'Male' ? 'bg-blue-50 text-blue-700 dark:bg-blue-950/50 dark:text-blue-300' : 'bg-pink-50 text-pink-700 dark:bg-pink-950/50 dark:text-pink-300' }}">
                                    {{ $voice['gender'] === 'Male' ? '👨 Male' : '👩 Female' }}
                                </span>
                            </div>

                            <!-- Personality & Language -->
                            <div class="space-y-1 text-xs">
                                <div class="flex items-center gap-1 text-zinc-500">
                                    <span class="font-semibold text-zinc-700 dark:text-zinc-300">{{ $voice['language'] }}</span>
                                </div>
                                <p class="text-[11px] text-zinc-600 dark:text-zinc-400 line-clamp-2">
                                    {{ $voice['personality'] }}
                                </p>
                            </div>

                            <!-- In-Browser HTML5 Audio Player -->
                            <div class="pt-2">
                                <audio controls preload="none" class="w-full h-8 rounded-lg">
                                    <source src="{{ asset('storage/voice_samples/' . $voice['key'] . '.mp3') }}" type="audio/mpeg">
                                    {{ __('Your browser does not support audio playback.') }}
                                </audio>
                            </div>
                        </div>

                        <!-- Card Action -->
                        <div class="mt-3.5 flex items-center justify-between border-t border-zinc-100 pt-2.5 dark:border-zinc-800 text-xs">
                            <span class="text-[10px] text-zinc-400">{{ $voice['category'] }}</span>
                            @if (! $isCurrentDefault)
                                <button 
                                    wire:click="selectVoiceAsDefault('{{ $voice['key'] }}', '{{ $voice['name'] }}')"
                                    class="rounded-lg bg-purple-600 px-2.5 py-1 text-[11px] font-bold text-white shadow-sm hover:bg-purple-700 transition-all"
                                >
                                    {{ __('Use as Default Narrator') }}
                                </button>
                            @else
                                <span class="text-[11px] font-bold text-purple-600 dark:text-purple-400">
                                    ✓ {{ __('Selected') }}
                                </span>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="col-span-full py-8 text-center text-xs text-zinc-500">
                        {{ __('No voices matched your search.') }}
                    </div>
                @endforelse
            </div>
        </div>
    @endif

    <!-- Connection Modal -->
    @if ($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 sm:p-6 backdrop-blur-sm overflow-hidden" wire:keydown.escape="$set('showModal', false)">
            <div class="relative flex flex-col w-full max-w-2xl max-h-[90vh] rounded-2xl border border-zinc-200 bg-white shadow-2xl dark:border-zinc-800 dark:bg-zinc-900 overflow-hidden">
                <!-- Sticky Header -->
                <div class="flex-shrink-0 flex items-center justify-between border-b border-zinc-100 px-6 py-4 dark:border-zinc-800 bg-zinc-50/80 dark:bg-zinc-800/50">
                    <div class="flex items-center gap-2.5">
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-100 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-zinc-900 dark:text-zinc-100">
                                {{ $editingConnectionId ? __('Edit AI Model Connection') : __('Add AI Model Connection') }}
                            </h3>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                {{ __('Configure provider endpoints, API credentials, and model schemas.') }}
                            </p>
                        </div>
                    </div>
                    <button 
                        type="button" 
                        wire:click="$set('showModal', false)" 
                        class="rounded-lg p-1.5 text-zinc-400 hover:bg-zinc-100 hover:text-zinc-600 dark:hover:bg-zinc-800 dark:hover:text-zinc-200 transition-colors"
                        aria-label="{{ __('Close dialog') }}"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <!-- Form Wrapping Scrollable Body & Sticky Action Footer -->
                <form wire:submit="saveConnection" class="flex flex-col flex-1 min-h-0">
                    <!-- Scrollable Form Body -->
                    <div class="flex-1 overflow-y-auto overscroll-contain px-6 py-5 space-y-4 text-xs">
                        <div>
                            <flux:input wire:model="formName" :label="__('Display Name')" placeholder="e.g., Together AI - FLUX.1 Schnell" />
                            @error('formName') <p class="text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <flux:select wire:model="formCategory" :label="__('Category')">
                                    <flux:select.option value="text">{{ __('Text / LLM') }}</flux:select.option>
                                    <flux:select.option value="image">{{ __('Image Generation') }}</flux:select.option>
                                    <flux:select.option value="audio_tts">{{ __('Voiceover (TTS)') }}</flux:select.option>
                                    <flux:select.option value="audio_stt">{{ __('Transcription (STT)') }}</flux:select.option>
                                </flux:select>
                            </div>
                            <div>
                                <flux:select wire:model="formProvider" :label="__('Transporter Provider')">
                                    <flux:select.option value="gemini">Google Gemini (LLM)</flux:select.option>
                                    <flux:select.option value="docker_cog">{{ __('Docker (Cog / Local Replicate)') }}</flux:select.option>
                                    <flux:select.option value="fal_ai">Fal.ai (FLUX 1.1 Pro / Dev)</flux:select.option>
                                    <flux:select.option value="replicate">Replicate (FLUX Pro / Dev / Schnell)</flux:select.option>
                                    <flux:select.option value="together_ai">Together AI (FLUX Schnell / Dev)</flux:select.option>
                                    <flux:select.option value="stability_ai">Stability AI (SD 3.5 Large / Flash)</flux:select.option>
                                    <flux:select.option value="gemini_image">Google AI Studio (Gemini Flash Image)</flux:select.option>
                                    <flux:select.option value="huggingface">HuggingFace Serverless</flux:select.option>
                                    <flux:select.option value="cloudflare">Cloudflare Workers AI</flux:select.option>
                                    <flux:select.option value="pollinations">Pollinations.ai (Free)</flux:select.option>
                                    <flux:select.option value="openai_compatible">OpenAI / Compatible REST API</flux:select.option>
                                    <flux:select.option value="groq">Groq LPU</flux:select.option>
                                    <flux:select.option value="elevenlabs">ElevenLabs</flux:select.option>
                                    <flux:select.option value="edge_tts">Edge-TTS (Local)</flux:select.option>
                                    <flux:select.option value="whisper_local">Whisper (Local Python)</flux:select.option>
                                    <flux:select.option value="custom_http">Custom HTTP Webhook</flux:select.option>
                                </flux:select>
                            </div>
                        </div>

                        <div>
                            <flux:input wire:model="formModelId" :label="__('Model Identifier / String')" placeholder="e.g., black-forest-labs/FLUX.1-schnell or id-ID-ArdiNeural" />
                            @error('formModelId') <p class="text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <flux:input wire:model="formBaseUrl" :label="__('Base URL / Endpoint (Optional)')" placeholder="e.g., https://api.together.xyz/v1 or worker URL" />
                            @if ($formProvider === 'replicate')
                                <p class="mt-1 text-[11px] text-emerald-600 dark:text-emerald-400 font-mono">
                                    🔔 Webhook endpoint: {{ url('/api/webhooks/replicate') }} (Dapat diexpose via Cloudflare Tunnel / REPLICATE_WEBHOOK_URL)
                                </p>
                            @endif
                            @if ($formProvider === 'docker_cog' || $formProvider === 'docker_replicate')
                                <p class="mt-1 text-[11px] text-emerald-600 dark:text-emerald-400 font-mono">
                                    🐳 {{ __('Local Docker Cog container (e.g. http://localhost:5000/predictions). No API key required.') }}
                                </p>
                            @endif
                        </div>

                        <div>
                            <flux:input type="password" wire:model="formApiKey" :label="__('API Key / Secret Token (Encrypted at rest)')" placeholder="sk-..." />
                            <p class="mt-1 text-[11px] text-zinc-400">{{ __('Leave empty if using local/free models or if retaining existing key.') }}</p>
                        </div>

                        @if ($formCategory === 'image')
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div>
                                    <flux:input type="number" wire:model="formNumSteps" :label="__('Inference Steps')" placeholder="20" min="1" max="100" />
                                    <p class="mt-1 text-[10px] text-zinc-400">{{ __('20-25 steps') }}</p>
                                </div>
                                <div>
                                    <flux:input type="number" step="0.05" wire:model="formStrength" :label="__('Default Strength')" placeholder="0.75" min="0" max="1" />
                                    <p class="mt-1 text-[10px] text-zinc-400">{{ __('0.0 to 1.0 (Img2Img)') }}</p>
                                </div>
                                <div>
                                    <flux:input type="number" step="0.5" wire:model="formGuidance" :label="__('Default Guidance')" placeholder="7.5" min="1" max="20" />
                                    <p class="mt-1 text-[10px] text-zinc-400">{{ __('1.0 to 20.0 (CFG)') }}</p>
                                </div>
                            </div>
                        @endif

                        @if ($formProvider === 'cloudflare' || $formCategory === 'image')
                            <div>
                                <flux:textarea 
                                    wire:model="formApiSchema" 
                                    :label="__('Dynamic API Schema (JSON Schema)')" 
                                    placeholder="{{ __('Enter custom JSON Schema for this model (e.g. multipart schema for Klein 9B, prompt/guidance/seed for Phoenix-1.0)...') }}"
                                    rows="4"
                                    class="font-mono text-xs"
                                />
                                <p class="mt-1 text-[11px] text-zinc-400">
                                    {{ __('Defines the exact JSON Schema for this model (e.g. multipart for Klein 9B, or standard prompt/guidance/seed for Leonardo Phoenix 1.0).') }}
                                </p>
                                @error('formApiSchema') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                        @endif

                        <div class="flex items-center gap-6 pt-1">
                            <label class="flex items-center gap-2 cursor-pointer select-none">
                                <input type="checkbox" wire:model="formIsDefault" class="rounded text-emerald-600 focus:ring-emerald-500">
                                <span class="font-medium text-zinc-700 dark:text-zinc-300">{{ __('Set as Default for this category') }}</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer select-none">
                                <input type="checkbox" wire:model="formIsActive" class="rounded text-emerald-600 focus:ring-emerald-500">
                                <span class="font-medium text-zinc-700 dark:text-zinc-300">{{ __('Enabled') }}</span>
                            </label>
                        </div>
                    </div>

                    <!-- Sticky Action Footer -->
                    <div class="flex-shrink-0 flex items-center justify-between border-t border-zinc-100 px-6 py-4 dark:border-zinc-800 bg-zinc-50/90 dark:bg-zinc-800/60 backdrop-blur-sm">
                        <span class="text-[11px] text-zinc-400">
                            {{ $editingConnectionId ? __('Changes take effect immediately.') : __('All fields can be updated later.') }}
                        </span>
                        <div class="flex items-center gap-3">
                            <flux:button type="button" wire:click="$set('showModal', false)" variant="ghost" class="text-zinc-600 dark:text-zinc-300">
                                {{ __('Cancel') }}
                            </flux:button>
                            <flux:button type="submit" variant="primary" class="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold shadow-sm">
                                {{ $editingConnectionId ? __('Save Changes') : __('Save Connection') }}
                            </flux:button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
