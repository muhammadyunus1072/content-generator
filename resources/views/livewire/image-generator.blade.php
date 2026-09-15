<div class="w-full space-y-6">
    <!-- Header -->
    <div class="relative overflow-hidden rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="absolute -right-16 -top-16 h-32 w-32 rounded-full bg-blue-500/10 blur-3xl dark:bg-blue-400/10"></div>
        <div class="absolute -bottom-16 -left-16 h-32 w-32 rounded-full bg-purple-500/10 blur-3xl dark:bg-purple-400/10"></div>
        
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <flux:heading size="xl" level="1" class="bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 bg-clip-text text-transparent font-bold dark:from-blue-400 dark:via-indigo-400 dark:to-purple-400">
                        {{ __('AI Image Generator & Director Sandbox') }}
                    </flux:heading>
                    <span class="inline-flex items-center gap-1 rounded-full bg-indigo-50 px-2.5 py-0.5 text-[11px] font-bold text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
                        🎬 {{ __('AI Director Engine') }}
                    </span>
                </div>
                <flux:subheading>{{ __('Generate studio visuals with the AI Content Director Engine (multi-subject, extreme macro close-ups, dynamic mood) or raw prompts.') }}</flux:subheading>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('ai-config') }}" class="inline-flex items-center gap-1.5 rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-1.5 text-xs font-medium text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700 transition-colors">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                    {{ __('Manage AI Models') }}
                </a>
            </div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
        
        <!-- Left Column: Controls & Prompt Engine (7 cols) -->
        <div class="lg:col-span-7 space-y-6">

            <!-- Mode Selector Switcher -->
            <div class="flex items-center justify-between rounded-xl border border-zinc-200 bg-white p-1.5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <div class="grid grid-cols-2 w-full gap-1.5">
                    <button
                        type="button"
                        wire:click="$set('promptMode', 'director')"
                        class="flex items-center justify-center gap-2 rounded-lg px-3 py-2 text-xs font-semibold transition-all cursor-pointer {{ $promptMode === 'director' ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-sm' : 'text-zinc-600 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800' }}"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z" /></svg>
                        <span>{{ __('Director Template Engine') }}</span>
                        <span class="rounded-full bg-white/20 px-1.5 py-0.2 text-[10px] font-mono">{{ __('Structured') }}</span>
                    </button>

                    <button
                        type="button"
                        wire:click="$set('promptMode', 'raw')"
                        class="flex items-center justify-center gap-2 rounded-lg px-3 py-2 text-xs font-semibold transition-all cursor-pointer {{ $promptMode === 'raw' ? 'bg-zinc-900 text-white shadow-sm dark:bg-zinc-100 dark:text-zinc-900' : 'text-zinc-600 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800' }}"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                        <span>{{ __('Raw Prompt Mode') }}</span>
                        <span class="rounded-full bg-zinc-200/60 dark:bg-zinc-700 px-1.5 py-0.2 text-[10px] font-mono">{{ __('Direct') }}</span>
                    </button>
                </div>
            </div>

            <form wire:submit="generateImage" class="space-y-6">

                <!-- DIRECTOR TEMPLATE ENGINE SECTION -->
                @if ($promptMode === 'director')
                    <div class="space-y-6">
                        
                        <!-- Quick Test Presets Bar -->
                        <div class="rounded-xl border border-indigo-100 bg-indigo-50/40 p-4 shadow-sm dark:border-indigo-900/40 dark:bg-indigo-950/20">
                            <div class="flex items-center justify-between mb-2.5">
                                <span class="text-xs font-bold uppercase tracking-wider text-indigo-700 dark:text-indigo-300 flex items-center gap-1.5">
                                    ⚡ {{ __('1-Click Test Scenarios (Director Templates)') }}
                                </span>
                                <span class="text-[11px] text-zinc-500 dark:text-zinc-400">{{ __('Instant manhwa scene synthesis') }}</span>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <button
                                    type="button"
                                    wire:click="loadPreset('manhwa_action')"
                                    class="inline-flex items-center gap-1.5 rounded-lg border border-indigo-200 bg-white px-2.5 py-1.5 text-xs font-medium text-indigo-800 shadow-sm hover:bg-indigo-50 dark:border-indigo-800 dark:bg-zinc-900 dark:text-indigo-300 dark:hover:bg-indigo-950/50 transition-colors cursor-pointer"
                                >
                                    ⚔️ <span>{{ __('Manhwa Duel / Action') }}</span>
                                </button>

                                <button
                                    type="button"
                                    wire:click="loadPreset('macro_eyes')"
                                    class="inline-flex items-center gap-1.5 rounded-lg border border-purple-200 bg-white px-2.5 py-1.5 text-xs font-medium text-purple-800 shadow-sm hover:bg-purple-50 dark:border-purple-800 dark:bg-zinc-900 dark:text-purple-300 dark:hover:bg-purple-950/50 transition-colors cursor-pointer"
                                >
                                    👁️ <span>{{ __('Macro Glowing Eyes') }}</span>
                                </button>

                                <button
                                    type="button"
                                    wire:click="loadPreset('macro_fist')"
                                    class="inline-flex items-center gap-1.5 rounded-lg border border-amber-200 bg-white px-2.5 py-1.5 text-xs font-medium text-amber-800 shadow-sm hover:bg-amber-50 dark:border-amber-800 dark:bg-zinc-900 dark:text-amber-300 dark:hover:bg-amber-950/50 transition-colors cursor-pointer"
                                >
                                    👊 <span>{{ __('Macro Clenched Fist') }}</span>
                                </button>

                                <button
                                    type="button"
                                    wire:click="loadPreset('bustling_crowd')"
                                    class="inline-flex items-center gap-1.5 rounded-lg border border-emerald-200 bg-white px-2.5 py-1.5 text-xs font-medium text-emerald-800 shadow-sm hover:bg-emerald-50 dark:border-emerald-800 dark:bg-zinc-900 dark:text-emerald-300 dark:hover:bg-emerald-950/50 transition-colors cursor-pointer"
                                >
                                    👥 <span>{{ __('Bustling Crowd') }}</span>
                                </button>

                                <button
                                    type="button"
                                    wire:click="loadPreset('prop_relic')"
                                    class="inline-flex items-center gap-1.5 rounded-lg border border-rose-200 bg-white px-2.5 py-1.5 text-xs font-medium text-rose-800 shadow-sm hover:bg-rose-50 dark:border-rose-800 dark:bg-zinc-900 dark:text-rose-300 dark:hover:bg-rose-950/50 transition-colors cursor-pointer"
                                >
                                    🗡️ <span>{{ __('Cursed Relic Prop') }}</span>
                                </button>

                                <button
                                    type="button"
                                    wire:click="loadPreset('landscape')"
                                    class="inline-flex items-center gap-1.5 rounded-lg border border-cyan-200 bg-white px-2.5 py-1.5 text-xs font-medium text-cyan-800 shadow-sm hover:bg-cyan-50 dark:border-cyan-800 dark:bg-zinc-900 dark:text-cyan-300 dark:hover:bg-cyan-950/50 transition-colors cursor-pointer"
                                >
                                    🌄 <span>{{ __('Epic Landscape') }}</span>
                                </button>
                            </div>
                        </div>

                        <!-- Card 1: Universe & Global Style Foundation (Levels 1, 2, 5) -->
                        <div x-data="{ showStyleDetails: true }" class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 space-y-4">
                            <div class="flex items-center justify-between border-b border-zinc-100 dark:border-zinc-800 pb-3">
                                <div>
                                    <h3 class="text-sm font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                                        <span class="flex h-5 w-5 items-center justify-center rounded-full bg-blue-100 text-[11px] font-bold text-blue-700 dark:bg-blue-900/60 dark:text-blue-300">1</span>
                                        {{ __('Global Universe & Style (Brain Key)') }}
                                    </h3>
                                    <p class="text-xs text-zinc-500">{{ __('Sets the fundamental visual identity shared across all panels.') }}</p>
                                </div>

                                <button type="button" x-on:click="showStyleDetails = !showStyleDetails" class="text-xs text-zinc-400 hover:text-zinc-600 font-medium">
                                    <span x-text="showStyleDetails ? '{{ __('Collapse') }}' : '{{ __('Customize Formula') }}'"></span>
                                </button>
                            </div>

                            <!-- Title / Universe Picker -->
                            <div>
                                <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">
                                    {{ __('Linked Content Title / Universe') }}
                                </label>
                                <flux:select wire:model.live="selectedContentTitleId">
                                    <flux:select.option value="">{{ __('[ Custom / Standalone Sandbox Universe ]') }}</flux:select.option>
                                    @foreach ($userTitles as $t)
                                        <flux:select.option value="{{ $t->id }}">
                                            {{ $t->title }} ({{ $t->genre ?? 'General' }})
                                        </flux:select.option>
                                    @endforeach
                                </flux:select>
                                @if ($activeTitle)
                                    <p class="mt-1 text-[11px] text-emerald-600 dark:text-emerald-400 flex items-center gap-1 font-medium">
                                        <span>✓</span> {{ __('Loaded style & asset library from:') }} <strong class="underline">{{ $activeTitle->title }}</strong>
                                    </p>
                                @endif
                            </div>

                            <!-- Levels 1, 2, 5 Detail Drawer -->
                            <div x-show="showStyleDetails" class="space-y-3 pt-2">
                                <div>
                                    <label class="block text-[11px] font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400 mb-1">
                                        {{ __('Level 1: Base Art Style & Medium') }}
                                    </label>
                                    <flux:input wire:model.live.debounce.400ms="level1" placeholder="{{ __('e.g. Digital Manhwa Webtoon illustration, crisp dynamic lineart, vibrant cel-shading') }}" />
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-[11px] font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400 mb-1">
                                            {{ __('Level 2: Sub-Genre & Aesthetic') }}
                                        </label>
                                        <flux:input wire:model.live.debounce.400ms="level2" placeholder="{{ __('e.g. Dark Fantasy Hunter Dungeon, high-stakes apocalypse') }}" />
                                    </div>

                                    <div>
                                        <label class="block text-[11px] font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400 mb-1">
                                            {{ __('Level 5: Render Engine & Modifiers') }}
                                        </label>
                                        <flux:input wire:model.live.debounce.400ms="level5" placeholder="{{ __('e.g. Unreal Engine 5 render, raytracing, 8k, masterpiece') }}" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Card 2: Dynamic Scene Directing (Levels 3 & 4) -->
                        <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 space-y-4">
                            <div class="border-b border-zinc-100 dark:border-zinc-800 pb-3">
                                <h3 class="text-sm font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                                    <span class="flex h-5 w-5 items-center justify-center rounded-full bg-purple-100 text-[11px] font-bold text-purple-700 dark:bg-purple-900/60 dark:text-purple-300">2</span>
                                    {{ __('Dynamic Scene Directing (Levels 3 & 4)') }}
                                </h3>
                                <p class="text-xs text-zinc-500">{{ __('The Content Director flow: dynamically controls subjects, shot framing, action, environment & lighting.') }}</p>
                            </div>

                            <!-- Focus Type & Shot Framing -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">
                                        {{ __('Director Focus Type') }}
                                    </label>
                                    <flux:select wire:model.live="focusType">
                                        <flux:select.option value="single_character">👤 {{ __('Single Character Focus') }}</flux:select.option>
                                        <flux:select.option value="interaction">⚔️ {{ __('Multi-Subject / Interaction') }}</flux:select.option>
                                        <flux:select.option value="body_part">👁️ {{ __('Macro / Body Part Close-Up') }}</flux:select.option>
                                        <flux:select.option value="prop">🗡️ {{ __('Prop / Weapon / Artifact') }}</flux:select.option>
                                        <flux:select.option value="environment">🏰 {{ __('Environment / Landscape') }}</flux:select.option>
                                        <flux:select.option value="crowd">👥 {{ __('Crowd / Ensemble Scene') }}</flux:select.option>
                                    </flux:select>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">
                                        {{ __('Shot Framing / Camera Distance') }}
                                    </label>
                                    <flux:input wire:model.live.debounce.400ms="shotFraming" placeholder="{{ __('e.g. dynamic mid-shot action scene, extreme macro close-up on eyes') }}" />
                                </div>
                            </div>

                            <!-- Primary Subject & Secondary Subjects -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <div class="flex items-center justify-between mb-1">
                                        <label class="text-xs font-semibold text-zinc-700 dark:text-zinc-300">
                                            {{ __('Primary Subject') }}
                                        </label>
                                        @if ($availableCharacters->isNotEmpty())
                                            <span class="text-[10px] text-zinc-400">{{ __('Chars available') }}</span>
                                        @endif
                                    </div>
                                    <flux:input wire:model.live.debounce.400ms="primarySubject" placeholder="{{ __('e.g. Arthur, silver-trimmed black tunic, glowing azure runic blade') }}" />
                                    
                                    @if ($availableCharacters->isNotEmpty())
                                        <div class="flex flex-wrap gap-1 mt-1.5">
                                            @foreach ($availableCharacters->take(4) as $char)
                                                <button
                                                    type="button"
                                                    wire:click="$set('primarySubject', @js($char->name))"
                                                    class="inline-block rounded bg-zinc-100 px-1.5 py-0.5 text-[10px] text-zinc-700 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-300"
                                                >
                                                    + {{ $char->name }}
                                                </button>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">
                                        {{ __('Secondary Subject(s) (Optional)') }}
                                    </label>
                                    <flux:input wire:model.live.debounce.400ms="secondarySubjects" placeholder="{{ __('e.g. High Orc Chieftain in blood-red armor') }}" />
                                    <p class="mt-1 text-[10px] text-zinc-400">{{ __('Used for multi-character interactions, duels, or companions') }}</p>
                                </div>
                            </div>

                            <!-- Action Description / Movement -->
                            <div>
                                <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">
                                    {{ __('Action, Movement & Pose') }}
                                </label>
                                <flux:textarea wire:model.live.debounce.400ms="actionDescription" rows="2" placeholder="{{ __('e.g. clashing blade against club with sparks and mana shockwaves bursting violently') }}" />
                            </div>

                            <!-- Environment & Location -->
                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <label class="text-xs font-semibold text-zinc-700 dark:text-zinc-300">
                                        {{ __('Environment / Setting') }}
                                    </label>
                                    @if ($availableEnvironments->isNotEmpty())
                                        <span class="text-[10px] text-zinc-400">{{ __('Presets available') }}</span>
                                    @endif
                                </div>
                                <flux:input wire:model.live.debounce.400ms="environment" placeholder="{{ __('e.g. Ruined Temple Courtyard, cracked stone pillars and floating embers') }}" />

                                @if ($availableEnvironments->isNotEmpty())
                                    <div class="flex flex-wrap gap-1 mt-1.5">
                                        @foreach ($availableEnvironments->take(4) as $env)
                                            <button
                                                type="button"
                                                wire:click="$set('environment', @js($env->name))"
                                                class="inline-block rounded bg-zinc-100 px-1.5 py-0.5 text-[10px] text-zinc-700 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-300"
                                            >
                                                + {{ $env->name }}
                                            </button>
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                            <!-- Crowd Density & Dynamic Lighting (Level 4) -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">
                                        {{ __('Crowd Density') }}
                                    </label>
                                    <flux:select wire:model.live="crowdDensity">
                                        <flux:select.option value="none">🚫 {{ __('None (Isolated subjects)') }}</flux:select.option>
                                        <flux:select.option value="sparse">👤 {{ __('Sparse (Blurred onlookers)') }}</flux:select.option>
                                        <flux:select.option value="dense">👥 {{ __('Dense (Crowded background)') }}</flux:select.option>
                                        <flux:select.option value="bustling">🏮 {{ __('Bustling (Packed market/square)') }}</flux:select.option>
                                    </flux:select>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">
                                        {{ __('Lighting & Mood (Dynamic Level 4)') }}
                                    </label>
                                    <flux:input wire:model.live.debounce.400ms="lightingMood" placeholder="{{ __('e.g. Midnight, dramatic shadows, purple bioluminescence') }}" />
                                </div>
                            </div>
                        </div>

                        <!-- Card 3: Live Compiled Prompt Preview -->
                        <div class="rounded-xl border border-blue-200 bg-gradient-to-br from-blue-50/50 to-indigo-50/50 p-4 shadow-sm dark:border-blue-900/50 dark:from-zinc-900 dark:to-zinc-800/80 space-y-3">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="flex h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                    <span class="text-xs font-bold uppercase tracking-wider text-blue-800 dark:text-blue-300">
                                        {{ __('Synthesized Director Prompt (Live Output)') }}
                                    </span>
                                    <span class="text-[10px] font-mono text-zinc-400">({{ strlen($compiledPrompt) }} {{ __('chars') }})</span>
                                </div>

                                <div class="flex items-center gap-1.5">
                                    <button
                                        type="button"
                                        wire:click="syncToRawPrompt"
                                        class="inline-flex items-center gap-1 rounded-md bg-white px-2 py-1 text-[11px] font-medium text-zinc-700 shadow-sm border border-zinc-200 hover:bg-zinc-50 dark:bg-zinc-800 dark:text-zinc-200 dark:border-zinc-700 cursor-pointer"
                                        title="{{ __('Copy to Raw Prompt Mode to tweak manually') }}"
                                    >
                                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                                        {{ __('Open in Raw Mode') }}
                                    </button>

                                    <button
                                        type="button"
                                        x-data="{ copied: false }"
                                        x-on:click="
                                            navigator.clipboard.writeText(@js($compiledPrompt));
                                            copied = true;
                                            setTimeout(() => copied = false, 2000);
                                        "
                                        class="inline-flex items-center gap-1 rounded-md bg-white px-2 py-1 text-[11px] font-medium text-zinc-700 shadow-sm border border-zinc-200 hover:bg-zinc-50 dark:bg-zinc-800 dark:text-zinc-200 dark:border-zinc-700 cursor-pointer"
                                    >
                                        <span x-show="!copied">📋 {{ __('Copy') }}</span>
                                        <span x-show="copied" x-cloak class="text-emerald-600 dark:text-emerald-400 font-bold">✓ {{ __('Copied!') }}</span>
                                    </button>
                                </div>
                            </div>

                            <div class="rounded-lg border border-zinc-200/80 bg-white/90 p-3 text-xs leading-relaxed font-mono text-zinc-800 dark:border-zinc-700 dark:bg-zinc-950/80 dark:text-zinc-200 shadow-inner break-words">
                                {{ $compiledPrompt ?: __('Configuring scene director parameters...') }}
                            </div>
                        </div>

                    </div>
                @else
                    <!-- RAW PROMPT MODE SECTION -->
                    <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 space-y-3">
                        <div class="flex items-center justify-between">
                            <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                {{ __('Visual Prompt (Direct / Raw)') }}
                            </label>
                            <button
                                type="button"
                                wire:click="$set('promptMode', 'director')"
                                class="text-xs text-blue-600 hover:text-blue-700 font-medium"
                            >
                                🎬 {{ __('Switch to Director Mode') }}
                            </button>
                        </div>
                        <flux:textarea 
                            wire:model="prompt" 
                            placeholder="{{ __('Describe what you want to generate in rich visual detail...') }}" 
                            rows="5"
                        />
                        @error('prompt') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                @endif

                <!-- CORE AI ENGINE SETTINGS CARD -->
                <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 space-y-4">
                    <h3 class="text-sm font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-2 border-b border-zinc-100 dark:border-zinc-800 pb-2">
                        ⚙️ {{ __('Generation Engine & Output Settings') }}
                    </h3>

                    <!-- AI Model Selector -->
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                {{ __('AI Image Model') }}
                            </label>
                            @if ($supportsReferenceImages)
                                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-bold text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300">
                                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                    {{ __('Img2Img Supported') }}
                                </span>
                            @endif
                        </div>
                        <flux:select wire:model.live="selectedConnectionId">
                            @foreach ($availableModels as $model)
                                <flux:select.option value="{{ $model->id }}">
                                    {{ $model->name }} {{ $model->is_default ? '★' : '' }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>

                    <!-- Style Preset & Aspect Ratio -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <flux:select wire:model="style" :label="__('Style Filter')">
                                <flux:select.option value="none">{{ __('None (Raw / Governed by Director)') }}</flux:select.option>
                                <flux:select.option value="photorealistic">{{ __('Photorealistic 8K') }}</flux:select.option>
                                <flux:select.option value="anime">{{ __('Anime / Manga') }}</flux:select.option>
                                <flux:select.option value="manhwa">{{ __('Dark Fantasy Manhwa') }}</flux:select.option>
                                <flux:select.option value="cyberpunk">{{ __('Cyberpunk Neon') }}</flux:select.option>
                                <flux:select.option value="fantasy">{{ __('Epic Fantasy Art') }}</flux:select.option>
                                <flux:select.option value="cartoon">{{ __('3D Pixar Render') }}</flux:select.option>
                            </flux:select>
                        </div>
                        <div>
                            <flux:select wire:model="aspectRatio" :label="__('Aspect Ratio')">
                                <flux:select.option value="16:9">{{ __('16:9 Landscape') }}</flux:select.option>
                                <flux:select.option value="9:16">{{ __('9:16 Vertical (Webtoon / Mobile)') }}</flux:select.option>
                                <flux:select.option value="1:1">{{ __('1:1 Square (Panel)') }}</flux:select.option>
                            </flux:select>
                        </div>
                    </div>

                    <!-- Reference Image (Image-to-Image) Dropzone -->
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                {{ __('Reference Image (Optional)') }}
                            </label>
                            @if ($referenceImage)
                                <button type="button" wire:click="clearReferenceImage" class="text-xs text-red-500 hover:text-red-700 font-medium">
                                    {{ __('Remove') }}
                                </button>
                            @endif
                        </div>

                        @if (! $supportsReferenceImages)
                            <div class="rounded-lg border border-amber-200 bg-amber-50/50 p-2 text-[11px] text-amber-700 dark:border-amber-900/50 dark:bg-amber-950/20 dark:text-amber-300">
                                💡 {{ __('Current model is Text-to-Image. Switch to Cloudflare FLUX-2, Fal.ai, or Stability AI to use reference images.') }}
                            </div>
                        @endif

                        @if ($referenceImage)
                            <div class="relative overflow-hidden rounded-xl border border-blue-500/40 bg-blue-50/20 p-2 dark:bg-blue-950/20">
                                <div class="flex items-center gap-3">
                                    <img src="{{ $referenceImage->temporaryUrl() }}" alt="Reference Preview" class="h-14 w-14 rounded-lg object-cover border border-zinc-200 dark:border-zinc-700">
                                    <div class="flex-1 overflow-hidden">
                                        <p class="text-xs font-semibold text-zinc-900 dark:text-zinc-100 truncate">{{ $referenceImage->getClientOriginalName() }}</p>
                                        <p class="text-[10px] text-emerald-600 dark:text-emerald-400 font-medium">✓ {{ __('Reference image attached') }}</p>
                                    </div>
                                    <button type="button" wire:click="clearReferenceImage" class="rounded-lg p-1 text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                    </button>
                                </div>
                            </div>
                        @else
                            <label class="flex flex-col items-center justify-center rounded-xl border-2 border-dashed border-zinc-200 p-3 text-center cursor-pointer transition-colors hover:border-blue-500/60 hover:bg-zinc-50/60 dark:border-zinc-700 dark:hover:border-blue-500/60 dark:hover:bg-zinc-800/40">
                                <span class="text-xs font-medium text-zinc-700 dark:text-zinc-300">📷 {{ __('Upload Reference Image') }}</span>
                                <span class="text-[10px] text-zinc-400">{{ __('PNG, JPG, WebP up to 10MB') }}</span>
                                <input type="file" wire:model="referenceImage" accept="image/*" class="hidden">
                            </label>
                        @endif

                        <div wire:loading wire:target="referenceImage" class="text-xs text-blue-600 dark:text-blue-400 font-medium flex items-center gap-1.5 pt-1">
                            <svg class="animate-spin h-3.5 w-3.5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            {{ __('Uploading reference image...') }}
                        </div>
                        @error('referenceImage') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <!-- Advanced Tuning Drawer -->
                    <div x-data="{ showAdvanced: false }" class="space-y-3 rounded-xl border border-zinc-100 bg-zinc-50/50 p-3.5 dark:border-zinc-800 dark:bg-zinc-800/30">
                        <div class="flex items-center justify-between cursor-pointer" x-on:click="showAdvanced = !showAdvanced">
                            <span class="text-xs font-semibold text-zinc-700 dark:text-zinc-300 flex items-center gap-1.5">
                                🎛️ {{ __('Fine-Tuning & Guidance Controls') }}
                            </span>
                            <span class="text-[10px] text-zinc-400 font-mono" x-text="showAdvanced ? '▲ {{ __('Hide') }}' : '▼ {{ __('Show') }}'"></span>
                        </div>

                        <div x-show="showAdvanced" class="space-y-4 pt-2 border-t border-zinc-200/60 dark:border-zinc-700/60 text-xs">
                            <!-- Strength Slider -->
                            <div class="space-y-1">
                                <div class="flex items-center justify-between">
                                    <label class="text-[11px] font-medium text-zinc-600 dark:text-zinc-400">{{ __('Transformation Strength') }}</label>
                                    <span class="text-[11px] font-bold text-blue-600 dark:text-blue-400 font-mono">{{ $strength }}</span>
                                </div>
                                <input type="range" wire:model.live="strength" min="0.05" max="1.0" step="0.05" class="w-full accent-blue-600 h-1.5 bg-zinc-200 rounded-lg cursor-pointer dark:bg-zinc-700">
                            </div>

                            <!-- Guidance Scale Slider -->
                            <div class="space-y-1">
                                <div class="flex items-center justify-between">
                                    <label class="text-[11px] font-medium text-zinc-600 dark:text-zinc-400">{{ __('Prompt Guidance / CFG') }}</label>
                                    <span class="text-[11px] font-bold text-purple-600 dark:text-purple-400 font-mono">{{ $guidance }}</span>
                                </div>
                                <input type="range" wire:model.live="guidance" min="1.0" max="20.0" step="0.5" class="w-full accent-purple-600 h-1.5 bg-zinc-200 rounded-lg cursor-pointer dark:bg-zinc-700">
                            </div>

                            <!-- Negative Prompt -->
                            <div>
                                <flux:input wire:model="negativePrompt" :label="__('Negative Prompt (Optional)')" placeholder="{{ __('e.g. blurry, mutated hands, distorted face, low quality') }}" />
                            </div>
                        </div>
                    </div>

                    @if ($errorMessage)
                        <div class="rounded-lg border border-red-200 bg-red-50 p-3 text-xs text-red-600 dark:border-red-900/50 dark:bg-red-950/20 dark:text-red-400">
                            {{ $errorMessage }}
                        </div>
                    @endif

                    <!-- Submit / Generate Button -->
                    <flux:button 
                        type="submit" 
                        variant="primary" 
                        class="w-full bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 hover:from-blue-700 hover:via-indigo-700 hover:to-purple-700 text-white font-bold shadow-md py-3 flex items-center justify-center gap-2 cursor-pointer transition-all"
                        wire:loading.attr="disabled"
                    >
                        <span wire:loading.remove wire:target="generateImage" class="flex items-center gap-2">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                            </svg>
                            <span>{{ $promptMode === 'director' ? __('Synthesize Visual via Director Engine') : __('Generate Image') }}</span>
                        </span>
                        <span wire:loading wire:target="generateImage" class="flex items-center gap-2">
                            <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>{{ __('Synthesizing Visual...') }}</span>
                        </span>
                    </flux:button>
                </div>

            </form>
        </div>

        <!-- Right Column: Canvas Preview & History (5 cols) -->
        <div class="lg:col-span-5 space-y-6 lg:sticky lg:top-6 self-start">
            <div class="relative flex min-h-[480px] flex-col items-center justify-center rounded-2xl border border-zinc-200 bg-zinc-50 p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-950/40">
                
                <!-- Loading State -->
                <div wire:loading wire:target="generateImage" class="absolute inset-0 flex flex-col items-center justify-center p-6 space-y-4 bg-white/80 backdrop-blur-sm dark:bg-zinc-900/80 z-10 rounded-2xl">
                    <div class="relative h-[300px] w-[300px] max-w-full overflow-hidden rounded-xl bg-zinc-200 shadow-md dark:bg-zinc-800 animate-pulse">
                        <div class="absolute left-0 top-0 h-1.5 w-full bg-gradient-to-r from-blue-500 via-indigo-500 to-purple-500 blur-sm animate-[bounce_3s_infinite]"></div>
                    </div>
                    <div class="text-center space-y-1">
                        <p class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Synthesizing High-Resolution Image...') }}</p>
                        <p class="text-xs text-zinc-500">{{ __('Communicating with AI model provider.') }}</p>
                    </div>
                </div>

                <!-- Displaying Generated Image -->
                @if ($generatedImageUrl)
                    <div class="flex flex-col items-center space-y-4 w-full">
                        <div class="relative overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-lg dark:border-zinc-800 max-h-[460px] w-full flex items-center justify-center">
                            <img 
                                src="{{ $generatedImageUrl }}" 
                                alt="{{ __('AI Generated Image') }}" 
                                class="object-contain max-h-[440px] w-auto max-w-full transition-transform duration-300 hover:scale-[1.02]"
                            />
                        </div>
                        
                        <!-- Actions -->
                        <div class="flex items-center gap-2.5 w-full justify-center">
                            <a 
                                href="{{ $generatedImageUrl }}" 
                                download="generated-image.png"
                                target="_blank"
                                class="inline-flex items-center gap-1.5 rounded-lg bg-zinc-900 px-3.5 py-2 text-xs font-medium text-white hover:bg-zinc-800 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-200 transition-colors shadow-sm"
                            >
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                                {{ __('Download') }}
                            </a>

                            <button 
                                type="button"
                                x-data="{ copied: false }"
                                x-on:click="
                                    navigator.clipboard.writeText(@js($prompt));
                                    copied = true;
                                    setTimeout(() => copied = false, 2000);
                                "
                                class="inline-flex items-center gap-1.5 rounded-lg border border-zinc-200 bg-white px-3.5 py-2 text-xs font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800 transition-colors shadow-sm cursor-pointer"
                            >
                                <span x-show="!copied" class="flex items-center gap-1.5">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
                                    </svg>
                                    {{ __('Copy Prompt') }}
                                </span>
                                <span x-show="copied" x-cloak class="flex items-center gap-1.5 text-emerald-600 dark:text-emerald-400 font-bold">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    {{ __('Copied!') }}
                                </span>
                            </button>
                        </div>
                    </div>
                @else
                    <!-- Empty State -->
                    <div class="flex flex-col items-center justify-center p-6 text-center space-y-3">
                        <div class="relative flex h-14 w-14 items-center justify-center rounded-2xl bg-zinc-100 text-zinc-400 dark:bg-zinc-900 dark:text-zinc-600">
                            <div class="absolute inset-0 rounded-2xl bg-gradient-to-tr from-blue-500/20 to-purple-500/20 blur-sm"></div>
                            <svg class="relative h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div class="space-y-1">
                            <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Ready to Synthesize') }}</h3>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400 max-w-xs">{{ __('Pick a preset above or fine-tune the Director Engine parameters, then click Synthesize Visual.') }}</p>
                        </div>
                    </div>
                @endif

            </div>

            <!-- Session History Tray -->
            @if (! empty($generationHistory))
                <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 space-y-3">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <flux:icon.clock class="size-4 text-zinc-400" />
                            <h4 class="text-xs font-semibold text-zinc-700 uppercase tracking-wider dark:text-zinc-300">{{ __('Session History') }}</h4>
                        </div>
                        <span class="text-xs text-zinc-400 font-mono">{{ count($generationHistory) }} {{ __('visuals') }}</span>
                    </div>

                    <div class="grid grid-cols-3 sm:grid-cols-4 gap-2.5">
                        @foreach ($generationHistory as $index => $item)
                            <button
                                type="button"
                                wire:click="selectHistoryItem({{ $index }})"
                                class="group relative aspect-square overflow-hidden rounded-lg border-2 transition-all duration-150 {{ $generatedImageUrl === $item['url'] ? 'border-indigo-600 ring-2 ring-indigo-500/20 shadow-md' : 'border-zinc-200 hover:border-zinc-400 dark:border-zinc-700 dark:hover:border-zinc-500' }}"
                                title="{{ $item['prompt'] }}"
                            >
                                <img
                                    src="{{ $item['url'] }}"
                                    alt="Generated preview"
                                    class="h-full w-full object-cover transition-transform duration-200 group-hover:scale-105"
                                />
                                <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-transparent opacity-0 transition-opacity group-hover:opacity-100 flex flex-col justify-end p-1.5 text-left">
                                    <span class="text-[9px] text-white font-bold truncate">{{ $item['aspectRatio'] ?? '16:9' }}</span>
                                    <span class="text-[8px] text-indigo-300 font-mono">{{ ($item['mode'] ?? 'raw') === 'director' ? '🎬 Director' : '✍️ Raw' }}</span>
                                </div>
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif

        </div>
    </div>
</div>
