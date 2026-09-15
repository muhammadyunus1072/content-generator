<div class="w-full space-y-6">
    <!-- Top Content Title Banner -->
    <div class="relative overflow-hidden rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="absolute -right-16 -top-16 h-36 w-36 rounded-full bg-blue-500/10 blur-3xl dark:bg-blue-400/10"></div>
        <div class="absolute -bottom-16 -left-16 h-36 w-36 rounded-full bg-purple-500/10 blur-3xl dark:bg-purple-400/10"></div>

        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="space-y-1.5">
                <div class="flex items-center gap-2">
                    <a href="{{ route('story-studio') }}" class="text-xs font-semibold text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 flex items-center gap-1" wire:navigate>
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                        {{ __('All Titles') }}
                    </a>
                    <span class="text-zinc-300 dark:text-zinc-700">•</span>
                    <span class="rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-bold text-blue-800 dark:bg-blue-950/40 dark:text-blue-400">
                        {{ $contentTitle->genre }}
                    </span>
                    <span class="rounded-full bg-purple-100 px-2.5 py-0.5 text-xs font-bold text-purple-800 dark:bg-purple-950/40 dark:text-purple-400">
                        {{ $contentTitle->visual_style }}
                    </span>
                    <span class="rounded-full bg-zinc-100 px-2.5 py-0.5 text-xs font-bold text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                        {{ $contentTitle->aspect_ratio }}
                    </span>
                </div>

                <div class="flex items-center gap-3">
                    <flux:heading size="xl" level="1" class="font-bold text-zinc-900 dark:text-zinc-100">
                        {{ $contentTitle->title }}
                    </flux:heading>
                    <button 
                        type="button" 
                        wire:click="openEditTitleModal" 
                        class="inline-flex items-center gap-1 rounded-lg border border-zinc-200 bg-zinc-50 px-2 py-1 text-xs font-semibold text-zinc-600 hover:bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700 transition-colors"
                        title="{{ __('Edit Content Title Settings') }}"
                    >
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                        <span>{{ __('Edit Settings') }}</span>
                    </button>
                </div>

                @if ($contentTitle->description)
                    <flux:subheading class="text-xs max-w-3xl text-zinc-500">{{ $contentTitle->description }}</flux:subheading>
                @endif
            </div>

            <!-- Main Tab Switcher -->
            <div class="flex items-center gap-2 rounded-xl bg-zinc-100 p-1 dark:bg-zinc-800 self-start sm:self-center">
                <button 
                    wire:click="setTab('world_bible')"
                    class="rounded-lg px-4 py-2 text-xs font-bold transition-all flex items-center gap-1.5 {{ $activeTab === 'world_bible' ? 'bg-white text-zinc-900 shadow-sm dark:bg-zinc-700 dark:text-zinc-100' : 'text-zinc-500 hover:text-zinc-900 dark:hover:text-zinc-200' }}"
                >
                    📖 {{ __('World Bible Studio') }}
                </button>
                <button 
                    wire:click="setTab('stories')"
                    class="rounded-lg px-4 py-2 text-xs font-bold transition-all flex items-center gap-1.5 {{ $activeTab === 'stories' ? 'bg-white text-zinc-900 shadow-sm dark:bg-zinc-700 dark:text-zinc-100' : 'text-zinc-500 hover:text-zinc-900 dark:hover:text-zinc-200' }}"
                >
                    🎬 {{ __('Story Episodes') }} ({{ $contentTitle->stories->count() }})
                </button>
            </div>
        </div>

        <!-- Quick Stats Meta Bar -->
        <div class="mt-4 flex flex-wrap items-center gap-6 border-t border-zinc-100 pt-3 text-xs text-zinc-500 dark:border-zinc-800">
            <div class="flex items-center gap-1.5">
                <span>👤</span>
                <span class="text-zinc-400">{{ __('Characters & Beasts:') }}</span>
                <strong class="text-zinc-800 dark:text-zinc-200">{{ $contentTitle->characters->count() }}</strong>
            </div>
            <div class="flex items-center gap-1.5">
                <span>🏞️</span>
                <span class="text-zinc-400">{{ __('Locations & Sets:') }}</span>
                <strong class="text-zinc-800 dark:text-zinc-200">{{ $contentTitle->environments->count() }}</strong>
            </div>
            <div class="flex items-center gap-1.5">
                <span>⚔️</span>
                <span class="text-zinc-400">{{ __('Key Props & Gear:') }}</span>
                <strong class="text-zinc-800 dark:text-zinc-200">{{ $contentTitle->props->count() }}</strong>
            </div>
            <div class="flex items-center gap-1.5">
                <span>🎙️</span>
                <span class="text-zinc-400">{{ __('Narrator Voice:') }}</span>
                <strong class="text-zinc-800 dark:text-zinc-200">{{ $contentTitle->voice_name }}</strong>
            </div>
            <div class="flex items-center gap-1.5">
                <span>🤖</span>
                <span class="text-zinc-400">{{ __('Image Model:') }}</span>
                <strong class="text-zinc-800 dark:text-zinc-200">{{ $contentTitle->imageConnection ? $contentTitle->imageConnection->name : ucfirst($contentTitle->image_provider ?: 'default') }}</strong>
            </div>
            @if ($contentTitle->textConnection)
                <div class="flex items-center gap-1.5">
                    <span>🧠</span>
                    <span class="text-zinc-400">{{ __('LLM:') }}</span>
                    <strong class="text-zinc-800 dark:text-zinc-200">{{ $contentTitle->textConnection->name }}</strong>
                </div>
            @endif
        </div>
    </div>

    <!-- ======================================================= -->
    <!-- TAB 1: WORLD BIBLE FOUNDATION STUDIO                    -->
    <!-- ======================================================= -->
    @if ($activeTab === 'world_bible')
        <div class="space-y-6">
            <!-- Stage Navigation Pills -->
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between border-b border-zinc-200 pb-3 dark:border-zinc-800">
                <div class="flex items-center gap-2 overflow-x-auto">
                    <button 
                        wire:click="setBibleSection('characters')"
                        class="rounded-lg px-4 py-2 text-xs font-bold transition-all flex items-center gap-1.5 {{ $bibleSection === 'characters' ? 'bg-purple-600 text-white shadow-sm' : 'bg-zinc-100 text-zinc-600 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-400' }}"
                    >
                        👤 {{ __('Characters & Creatures') }} ({{ $contentTitle->characters->count() }})
                    </button>
                    <button 
                        wire:click="setBibleSection('environments')"
                        class="rounded-lg px-4 py-2 text-xs font-bold transition-all flex items-center gap-1.5 {{ $bibleSection === 'environments' ? 'bg-emerald-600 text-white shadow-sm' : 'bg-zinc-100 text-zinc-600 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-400' }}"
                    >
                        🏞️ {{ __('Environments & Locations') }} ({{ $contentTitle->environments->count() }})
                    </button>
                    <button 
                        wire:click="setBibleSection('props')"
                        class="rounded-lg px-4 py-2 text-xs font-bold transition-all flex items-center gap-1.5 {{ $bibleSection === 'props' ? 'bg-amber-600 text-white shadow-sm' : 'bg-zinc-100 text-zinc-600 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-400' }}"
                    >
                        ⚔️ {{ __('Key Props & Gear') }} ({{ $contentTitle->props->count() }})
                    </button>
                    <button 
                        wire:click="setBibleSection('art_direction')"
                        class="rounded-lg px-4 py-2 text-xs font-bold transition-all flex items-center gap-1.5 {{ $bibleSection === 'art_direction' ? 'bg-blue-600 text-white shadow-sm' : 'bg-zinc-100 text-zinc-600 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-400' }}"
                    >
                        🎨 {{ __('Art Direction & Lighting') }}
                    </button>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center gap-2 shrink-0">
                    <button 
                        wire:click="openAiWorldBibleModal('{{ rtrim($bibleSection, 's') === 'character' ? 'characters' : (rtrim($bibleSection, 's') === 'environment' ? 'environments' : (rtrim($bibleSection, 's') === 'prop' ? 'props' : 'all')) }}')"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-gradient-to-r from-amber-500 via-purple-600 to-indigo-600 px-4 py-2 text-xs font-bold text-white shadow-sm hover:from-amber-600 hover:via-purple-700 hover:to-indigo-700 transition-all shrink-0"
                    >
                        <span>✨</span>
                        {{ __('AI Generate World Bible') }}
                    </button>

                    @if ($bibleSection !== 'art_direction')
                        <button 
                            wire:click="openCreateAssetModal('{{ rtrim($bibleSection, 's') === 'character' ? 'character' : (rtrim($bibleSection, 's') === 'environment' ? 'environment' : 'prop') }}')"
                            class="inline-flex items-center gap-1.5 rounded-xl bg-gradient-to-r from-purple-600 to-blue-600 px-4 py-2 text-xs font-bold text-white shadow-sm hover:from-purple-700 hover:to-blue-700 transition-all shrink-0"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                            {{ $bibleSection === 'characters' ? __('Custom Character / Creature Builder') : ($bibleSection === 'environments' ? __('Add Location / Environment') : __('Add Prop / Gear')) }}
                        </button>
                    @endif
                </div>
            </div>

            <!-- SECTION A: CHARACTERS & CREATURES -->
            @if ($bibleSection === 'characters')
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-base font-bold text-zinc-900 dark:text-zinc-100">{{ __('Character Reference Sheets & Turnarounds') }}</h3>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Characters attached to this title. Every character has a 3-view turnaround reference sheet to enforce strict visual consistency across all storyboards.') }}</p>
                        </div>
                    </div>

                    @if ($contentTitle->characters->isEmpty())
                        <div class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-zinc-300 p-12 text-center dark:border-zinc-700">
                            <span class="text-4xl block mb-2">👤</span>
                            <h4 class="text-sm font-bold text-zinc-900 dark:text-zinc-100">{{ __('No characters in this World Bible yet') }}</h4>
                            <p class="mt-1 text-xs text-zinc-500 max-w-md">{{ __('Generate lore-consistent cast members using AI templates or build them manually with our step-by-step turnaround builder.') }}</p>
                            <div class="mt-4 flex flex-wrap items-center justify-center gap-3">
                                <button 
                                    wire:click="openAiWorldBibleModal('characters')"
                                    class="rounded-xl bg-gradient-to-r from-purple-600 to-indigo-600 px-4 py-2 text-xs font-bold text-white hover:from-purple-700 hover:to-indigo-700 shadow-sm flex items-center gap-1.5"
                                >
                                    <span>✨</span> {{ __('AI Generate Characters with Template') }}
                                </button>
                                <button 
                                    wire:click="openCreateAssetModal('character')"
                                    class="rounded-xl bg-zinc-800 px-4 py-2 text-xs font-bold text-white hover:bg-zinc-700 shadow-sm"
                                >
                                    + {{ __('Open Custom Character Builder') }}
                                </button>
                            </div>
                        </div>
                    @else
                        <div class="grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-3">
                            @foreach ($contentTitle->characters as $character)
                                @php
                                    $hasSheet = ! empty($character->sheet_image_path);
                                @endphp
                                <div class="flex flex-col justify-between rounded-2xl border border-purple-200/80 bg-white p-5 shadow-sm transition-all hover:border-purple-400 dark:border-zinc-700 dark:bg-zinc-900">
                                    <div class="space-y-3">
                                        <!-- Card Header -->
                                        <div class="flex items-start justify-between gap-2">
                                            <div>
                                                <h4 class="font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-1.5 text-sm">
                                                    {{ $character->name }}
                                                    @if ($hasSheet)
                                                        <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300">
                                                            ✓ {{ __('Sheet Active') }}
                                                        </span>
                                                    @endif
                                                </h4>
                                                <div class="flex items-center gap-1.5 text-[11px] text-zinc-400 mt-0.5">
                                                    <span class="rounded px-1.5 py-0.5 bg-zinc-100 dark:bg-zinc-800 uppercase font-semibold text-[10px]">{{ $character->entity_type ?? 'human' }}</span>
                                                    <span>•</span>
                                                    <span>{{ ucfirst($character->gender ?? 'female') }}</span>
                                                    @if ($character->age)
                                                        <span>•</span>
                                                        <span>{{ $character->age }} yrs</span>
                                                    @endif
                                                </div>
                                            </div>

                                            <div class="flex items-center gap-1">
                                                <button wire:click="editAsset('character', {{ $character->id }})" class="text-xs text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 font-semibold">
                                                    {{ __('Edit') }}
                                                </button>
                                                <button wire:click="deleteAsset('character', {{ $character->id }})" wire:confirm="{{ __('Unlink this character from title?') }}" class="text-xs text-red-400 hover:text-red-600">
                                                    {{ __('Remove') }}
                                                </button>
                                            </div>
                                        </div>

                                        <!-- 16:9 Reference Sheet / Portrait Preview -->
                                        <div class="relative overflow-hidden rounded-xl bg-zinc-950 aspect-video flex items-center justify-center border border-zinc-200 dark:border-zinc-800 group">
                                            @if ($character->sheet_image_path)
                                                <img src="{{ $character->sheet_image_url }}" alt="{{ $character->name }}" class="h-full w-full object-cover transition-all group-hover:scale-105">
                                                <button 
                                                    wire:click="inspectSheet('character', {{ $character->id }})"
                                                    class="absolute bottom-2 right-2 rounded-lg bg-black/75 px-3 py-1.5 text-[11px] font-bold text-white backdrop-blur-sm hover:bg-black transition-all shadow-md flex items-center gap-1"
                                                >
                                                    👁️ {{ __('Inspect Turnaround') }}
                                                </button>
                                            @elseif ($character->avatar_path)
                                                <img src="{{ $character->avatar_url }}" alt="{{ $character->name }}" class="h-full w-full object-cover">
                                            @else
                                                <div class="text-center p-4">
                                                    <span class="text-3xl block mb-1">📋</span>
                                                    <span class="text-xs text-zinc-400">{{ __('No reference sheet attached') }}</span>
                                                </div>
                                            @endif
                                        </div>

                                        <!-- Color Palette Swatches -->
                                        @if (! empty($character->sheet_data['color_palette']))
                                            <div class="flex flex-wrap gap-1.5 pt-1">
                                                @foreach (array_slice($character->sheet_data['color_palette'], 0, 3) as $swatch)
                                                    <span class="rounded-md bg-zinc-100 px-2 py-0.5 text-[10px] font-medium text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                                                        {{ is_array($swatch) ? ($swatch['name'].': '.$swatch['color']) : $swatch }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        @endif

                                        <!-- Consistency Prompt Tokens -->
                                        <div class="rounded-xl bg-zinc-50 p-2.5 text-xs text-zinc-600 dark:bg-zinc-800/60 dark:text-zinc-300 font-mono line-clamp-2 border border-zinc-100 dark:border-zinc-800">
                                            <span class="text-purple-600 dark:text-purple-400 font-bold block text-[10px] uppercase">{{ __('Consistency Tokens:') }}</span>
                                            {{ $character->getConsistencyTokens() }}
                                        </div>
                                    </div>

                                    <!-- Quick Action Bar -->
                                    <div class="mt-4 pt-3 border-t border-zinc-100 dark:border-zinc-800 flex items-center justify-between text-xs">
                                        @if ($inlineUploadTargetType === 'character' && $inlineUploadTargetId === $character->id)
                                            <div class="flex items-center gap-1">
                                                <input type="file" wire:model="inlineUploadFile" class="text-[11px] max-w-[140px]">
                                                <button 
                                                    wire:click="directUploadSheet('character', {{ $character->id }})" 
                                                    wire:loading.attr="disabled"
                                                    class="rounded-lg bg-purple-600 px-2.5 py-1 text-[11px] font-bold text-white hover:bg-purple-700"
                                                >
                                                    {{ __('Upload') }}
                                                </button>
                                            </div>
                                        @else
                                            <button 
                                                wire:click="$set('inlineUploadTargetType', 'character'); $set('inlineUploadTargetId', {{ $character->id }})"
                                                class="font-semibold text-purple-600 hover:text-purple-700 dark:text-purple-400 flex items-center gap-1"
                                            >
                                                📁 {{ $hasSheet ? __('Replace Sheet') : __('Upload Sheet') }}
                                            </button>
                                        @endif

                                        <button 
                                            wire:click="directGenerateSheet('character', {{ $character->id }})"
                                            wire:loading.attr="disabled"
                                            class="font-semibold text-blue-600 hover:text-blue-700 dark:text-blue-400 flex items-center gap-1"
                                        >
                                            ✨ {{ __('AI Generate Sheet') }}
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif

            <!-- SECTION B: ENVIRONMENTS & LOCATIONS -->
            @if ($bibleSection === 'environments')
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-base font-bold text-zinc-900 dark:text-zinc-100">{{ __('Location Concept Sheets & Atmospheres') }}</h3>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Interiors, exterior landmarks, day/night lighting presets, and texture blueprints.') }}</p>
                        </div>
                    </div>

                    @if ($contentTitle->environments->isEmpty())
                        <div class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-zinc-300 p-12 text-center dark:border-zinc-700">
                            <span class="text-4xl block mb-2">🏞️</span>
                            <h4 class="text-sm font-bold text-zinc-900 dark:text-zinc-100">{{ __('No environments in this World Bible yet') }}</h4>
                            <p class="mt-1 text-xs text-zinc-500 max-w-md">{{ __('Generate immersive locations, lighting setups, and atmospheres with AI or upload custom concept sheets.') }}</p>
                            <div class="mt-4 flex flex-wrap items-center justify-center gap-3">
                                <button 
                                    wire:click="openAiWorldBibleModal('environments')"
                                    class="rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 px-4 py-2 text-xs font-bold text-white hover:from-emerald-700 hover:to-teal-700 shadow-sm flex items-center gap-1.5"
                                >
                                    <span>✨</span> {{ __('AI Generate Locations with Template') }}
                                </button>
                                <button 
                                    wire:click="openCreateAssetModal('environment')"
                                    class="rounded-xl bg-zinc-800 px-4 py-2 text-xs font-bold text-white hover:bg-zinc-700 shadow-sm"
                                >
                                    + {{ __('Create First Location Sheet') }}
                                </button>
                            </div>
                        </div>
                    @else
                        <div class="grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-3">
                            @foreach ($contentTitle->environments as $env)
                                @php
                                    $hasSheet = ! empty($env->sheet_image_path);
                                @endphp
                                <div class="flex flex-col justify-between rounded-2xl border border-emerald-200/80 bg-white p-5 shadow-sm transition-all hover:border-emerald-400 dark:border-zinc-700 dark:bg-zinc-900">
                                    <div class="space-y-3">
                                        <!-- Card Header -->
                                        <div class="flex items-start justify-between gap-2">
                                            <div>
                                                <h4 class="font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-1.5 text-sm">
                                                    {{ $env->name }}
                                                    @if ($hasSheet)
                                                        <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300">
                                                            ✓ {{ __('Sheet Active') }}
                                                        </span>
                                                    @endif
                                                </h4>
                                                <div class="flex items-center gap-1.5 text-[11px] text-zinc-400 mt-0.5">
                                                    <span class="rounded px-1.5 py-0.5 bg-zinc-100 dark:bg-zinc-800 uppercase font-semibold text-[10px]">{{ $env->setting_type ?? 'interior' }}</span>
                                                    <span>•</span>
                                                    <span class="capitalize">{{ $env->time_of_day ?? 'night' }}</span>
                                                </div>
                                            </div>

                                            <div class="flex items-center gap-1">
                                                <button wire:click="editAsset('environment', {{ $env->id }})" class="text-xs text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 font-semibold">
                                                    {{ __('Edit') }}
                                                </button>
                                                <button wire:click="deleteAsset('environment', {{ $env->id }})" wire:confirm="{{ __('Delete location?') }}" class="text-xs text-red-400 hover:text-red-600">
                                                    {{ __('Delete') }}
                                                </button>
                                            </div>
                                        </div>

                                        <!-- 16:9 Concept Sheet Preview -->
                                        <div class="relative overflow-hidden rounded-xl bg-zinc-950 aspect-video flex items-center justify-center border border-zinc-200 dark:border-zinc-800 group">
                                            @if ($env->sheet_image_path)
                                                <img src="{{ $env->sheet_image_url }}" alt="{{ $env->name }}" class="h-full w-full object-cover transition-all group-hover:scale-105">
                                                <button 
                                                    wire:click="inspectSheet('environment', {{ $env->id }})"
                                                    class="absolute bottom-2 right-2 rounded-lg bg-black/75 px-3 py-1.5 text-[11px] font-bold text-white backdrop-blur-sm hover:bg-black transition-all shadow-md flex items-center gap-1"
                                                >
                                                    👁️ {{ __('Inspect Sheet') }}
                                                </button>
                                            @elseif ($env->image_path)
                                                <img src="{{ $env->image_url }}" alt="{{ $env->name }}" class="h-full w-full object-cover">
                                            @else
                                                <div class="text-center p-4">
                                                    <span class="text-3xl block mb-1">🏞️</span>
                                                    <span class="text-xs text-zinc-400">{{ __('No sheet attached') }}</span>
                                                </div>
                                            @endif
                                        </div>

                                        <!-- Consistency Tokens -->
                                        <div class="rounded-xl bg-zinc-50 p-2.5 text-xs text-zinc-600 dark:bg-zinc-800/60 dark:text-zinc-300 font-mono line-clamp-2 border border-zinc-100 dark:border-zinc-800">
                                            <span class="text-emerald-600 dark:text-emerald-400 font-bold block text-[10px] uppercase">{{ __('Consistency Tokens:') }}</span>
                                            {{ $env->getConsistencyTokens() }}
                                        </div>
                                    </div>

                                    <!-- Quick Actions -->
                                    <div class="mt-4 pt-3 border-t border-zinc-100 dark:border-zinc-800 flex items-center justify-between text-xs">
                                        @if ($inlineUploadTargetType === 'environment' && $inlineUploadTargetId === $env->id)
                                            <div class="flex items-center gap-1">
                                                <input type="file" wire:model="inlineUploadFile" class="text-[11px] max-w-[140px]">
                                                <button 
                                                    wire:click="directUploadSheet('environment', {{ $env->id }})" 
                                                    class="rounded-lg bg-emerald-600 px-2.5 py-1 text-[11px] font-bold text-white hover:bg-emerald-700"
                                                >
                                                    {{ __('Upload') }}
                                                </button>
                                            </div>
                                        @else
                                            <button 
                                                wire:click="$set('inlineUploadTargetType', 'environment'); $set('inlineUploadTargetId', {{ $env->id }})"
                                                class="font-semibold text-emerald-600 hover:text-emerald-700 dark:text-emerald-400"
                                            >
                                                📁 {{ $hasSheet ? __('Replace Sheet') : __('Upload Concept Sheet') }}
                                            </button>
                                        @endif

                                        <button 
                                            wire:click="directGenerateSheet('environment', {{ $env->id }})"
                                            class="font-semibold text-blue-600 hover:text-blue-700 dark:text-blue-400"
                                        >
                                            ✨ {{ __('AI Generate Sheet') }}
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif

            <!-- SECTION C: KEY PROPS & GEAR -->
            @if ($bibleSection === 'props')
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-base font-bold text-zinc-900 dark:text-zinc-100">{{ __('Key Props, Gear & Artifact Design Sheets') }}</h3>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Weapons, gadgets, tactical backpacks, and magical relics kept consistent across episodes.') }}</p>
                        </div>
                    </div>

                    @if ($contentTitle->props->isEmpty())
                        <div class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-zinc-300 p-12 text-center dark:border-zinc-700">
                            <span class="text-4xl block mb-2">⚔️</span>
                            <h4 class="text-sm font-bold text-zinc-900 dark:text-zinc-100">{{ __('No key props in this World Bible yet') }}</h4>
                            <p class="mt-1 text-xs text-zinc-500 max-w-md">{{ __('Generate signature weapons, gadgets, or artifacts with AI or design custom prop sheets.') }}</p>
                            <div class="mt-4 flex flex-wrap items-center justify-center gap-3">
                                <button 
                                    wire:click="openAiWorldBibleModal('props')"
                                    class="rounded-xl bg-gradient-to-r from-amber-600 to-orange-600 px-4 py-2 text-xs font-bold text-white hover:from-amber-700 hover:to-orange-700 shadow-sm flex items-center gap-1.5"
                                >
                                    <span>✨</span> {{ __('AI Generate Key Props with Template') }}
                                </button>
                                <button 
                                    wire:click="openCreateAssetModal('prop')"
                                    class="rounded-xl bg-zinc-800 px-4 py-2 text-xs font-bold text-white hover:bg-zinc-700 shadow-sm"
                                >
                                    + {{ __('Create First Prop Sheet') }}
                                </button>
                            </div>
                        </div>
                    @else
                        <div class="grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-3">
                            @foreach ($contentTitle->props as $prop)
                                @php
                                    $hasSheet = ! empty($prop->sheet_image_path);
                                @endphp
                                <div class="flex flex-col justify-between rounded-2xl border border-amber-200/80 bg-white p-5 shadow-sm transition-all hover:border-amber-400 dark:border-zinc-700 dark:bg-zinc-900">
                                    <div class="space-y-3">
                                        <!-- Card Header -->
                                        <div class="flex items-start justify-between gap-2">
                                            <div>
                                                <h4 class="font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-1.5 text-sm">
                                                    {{ $prop->name }}
                                                    @if ($hasSheet)
                                                        <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300">
                                                            ✓ {{ __('Sheet Active') }}
                                                        </span>
                                                    @endif
                                                </h4>
                                                <div class="flex items-center gap-1.5 text-[11px] text-zinc-400 mt-0.5">
                                                    <span class="rounded px-1.5 py-0.5 bg-zinc-100 dark:bg-zinc-800 uppercase font-semibold text-[10px]">{{ $prop->category ?? 'gear' }}</span>
                                                </div>
                                            </div>

                                            <div class="flex items-center gap-1">
                                                <button wire:click="editAsset('prop', {{ $prop->id }})" class="text-xs text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 font-semibold">
                                                    {{ __('Edit') }}
                                                </button>
                                                <button wire:click="deleteAsset('prop', {{ $prop->id }})" wire:confirm="{{ __('Delete prop?') }}" class="text-xs text-red-400 hover:text-red-600">
                                                    {{ __('Delete') }}
                                                </button>
                                            </div>
                                        </div>

                                        <!-- 16:9 Prop Sheet Preview -->
                                        <div class="relative overflow-hidden rounded-xl bg-zinc-950 aspect-video flex items-center justify-center border border-zinc-200 dark:border-zinc-800 group">
                                            @if ($prop->sheet_image_path)
                                                <img src="{{ $prop->sheet_image_url }}" alt="{{ $prop->name }}" class="h-full w-full object-cover transition-all group-hover:scale-105">
                                                <button 
                                                    wire:click="inspectSheet('prop', {{ $prop->id }})"
                                                    class="absolute bottom-2 right-2 rounded-lg bg-black/75 px-3 py-1.5 text-[11px] font-bold text-white backdrop-blur-sm hover:bg-black transition-all shadow-md flex items-center gap-1"
                                                >
                                                    👁️ {{ __('Inspect Sheet') }}
                                                </button>
                                            @elseif ($prop->image_path)
                                                <img src="{{ $prop->image_url }}" alt="{{ $prop->name }}" class="h-full w-full object-cover">
                                            @else
                                                <div class="text-center p-4">
                                                    <span class="text-3xl block mb-1">⚔️</span>
                                                    <span class="text-xs text-zinc-400">{{ __('No sheet attached') }}</span>
                                                </div>
                                            @endif
                                        </div>

                                        <!-- Consistency Tokens -->
                                        <div class="rounded-xl bg-zinc-50 p-2.5 text-xs text-zinc-600 dark:bg-zinc-800/60 dark:text-zinc-300 font-mono line-clamp-2 border border-zinc-100 dark:border-zinc-800">
                                            <span class="text-amber-600 dark:text-amber-400 font-bold block text-[10px] uppercase">{{ __('Consistency Tokens:') }}</span>
                                            {{ $prop->getConsistencyTokens() }}
                                        </div>
                                    </div>

                                    <!-- Quick Actions -->
                                    <div class="mt-4 pt-3 border-t border-zinc-100 dark:border-zinc-800 flex items-center justify-between text-xs">
                                        @if ($inlineUploadTargetType === 'prop' && $inlineUploadTargetId === $prop->id)
                                            <div class="flex items-center gap-1">
                                                <input type="file" wire:model="inlineUploadFile" class="text-[11px] max-w-[140px]">
                                                <button 
                                                    wire:click="directUploadSheet('prop', {{ $prop->id }})" 
                                                    class="rounded-lg bg-amber-600 px-2.5 py-1 text-[11px] font-bold text-white hover:bg-amber-700"
                                                >
                                                    {{ __('Upload') }}
                                                </button>
                                            </div>
                                        @else
                                            <button 
                                                wire:click="$set('inlineUploadTargetType', 'prop'); $set('inlineUploadTargetId', {{ $prop->id }})"
                                                class="font-semibold text-amber-600 hover:text-amber-700 dark:text-amber-400"
                                            >
                                                📁 {{ $hasSheet ? __('Replace Sheet') : __('Upload Prop Sheet') }}
                                            </button>
                                        @endif

                                        <button 
                                            wire:click="directGenerateSheet('prop', {{ $prop->id }})"
                                            class="font-semibold text-blue-600 hover:text-blue-700 dark:text-blue-400"
                                        >
                                            ✨ {{ __('AI Generate Sheet') }}
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif

            <!-- SECTION D: ART DIRECTION & LIGHTING -->
            @if ($bibleSection === 'art_direction')
                <div class="max-w-4xl space-y-6">
                    <!-- Quick Signature Style Presets -->
                    <div class="rounded-2xl border border-blue-200 bg-blue-50/30 p-5 dark:border-blue-900/50 dark:bg-blue-950/20 space-y-3">
                        <div class="flex items-center gap-2">
                            <span class="text-base">✨</span>
                            <h4 class="text-xs font-bold text-blue-800 dark:text-blue-300 uppercase tracking-wider">{{ __('1-Click Art Direction Style Presets') }}</h4>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <button 
                                wire:click="applyArtPreset('solo_leveling')"
                                class="rounded-xl border border-purple-300 bg-white px-3.5 py-2 text-xs font-bold text-purple-900 shadow-sm hover:bg-purple-50 dark:border-purple-800 dark:bg-zinc-900 dark:text-purple-300"
                            >
                                ⚡ Solo Leveling (Dark Mana & Glowing Eyes)
                            </button>
                            <button 
                                wire:click="applyArtPreset('cyberpunk_shibuya')"
                                class="rounded-xl border border-blue-300 bg-white px-3.5 py-2 text-xs font-bold text-blue-900 shadow-sm hover:bg-blue-50 dark:border-blue-800 dark:bg-zinc-900 dark:text-blue-300"
                            >
                                ⚡ Cyberpunk Shibuya (Neon Screens & Rain Bokeh)
                            </button>
                            <button 
                                wire:click="applyArtPreset('fantasy_forest')"
                                class="rounded-xl border border-emerald-300 bg-white px-3.5 py-2 text-xs font-bold text-emerald-900 shadow-sm hover:bg-emerald-50 dark:border-emerald-800 dark:bg-zinc-900 dark:text-emerald-300"
                            >
                                ⚡ Whispering Woods (Bioluminescent Mist & God Rays)
                            </button>
                        </div>
                    </div>

                    <!-- Visual Rules Form -->
                    <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 space-y-5">
                        <div>
                            <flux:input wire:model="artColorPalette" :label="__('Color Palette Signature')" placeholder="e.g., Charcoal dark aluminum, neon lavender, electric magenta, cyan holographic glow" />
                            <p class="text-[11px] text-zinc-400 mt-1">{{ __('Primary and accent color tones maintained in every storyboard shot.') }}</p>
                        </div>

                        <div>
                            <flux:input wire:model="artLightingMood" :label="__('Lighting & Atmospheric Mood')" placeholder="e.g., Volumetric neon rain reflections, screen backlit glow, cinematic bokeh" />
                            <p class="text-[11px] text-zinc-400 mt-1">{{ __('Defines lighting contrast, volumetric haze, shadow depth, and glowing accents.') }}</p>
                        </div>

                        <div>
                            <flux:input wire:model="artCameraFraming" :label="__('Camera Framing & Lens Language')" placeholder="e.g., Over-the-shoulder workstation angles, wide Shibuya night skyline pans, 35mm lens" />
                            <p class="text-[11px] text-zinc-400 mt-1">{{ __('Default lens choices, perspective angles, and camera motion guidelines.') }}</p>
                        </div>

                        <div class="flex justify-end pt-2">
                            <flux:button wire:click="saveArtDirection" variant="primary" class="bg-blue-600 hover:bg-blue-700 text-white font-bold">
                                {{ __('Save Art Direction Rules') }}
                            </flux:button>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    @endif

    <!-- ======================================================= -->
    <!-- TAB 2: STORIES & EPISODES                               -->
    <!-- ======================================================= -->
    @if ($activeTab === 'stories')
        <div class="space-y-6">
            <!-- Create New Story / Episode Form -->
            <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <div class="mb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <h3 class="text-base font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                            <span>🎬</span> {{ __('Add New Story / Episode') }}
                        </h3>
                        <p class="text-xs text-zinc-500">
                            {{ __('Paste your episode script or auto-generate with AI based on your World Bible lore and templates.') }}
                        </p>
                    </div>
                    <button 
                        type="button"
                        wire:click="openAiEpisodeModal"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-gradient-to-r from-amber-500 via-pink-600 to-purple-600 px-4 py-2 text-xs font-bold text-white shadow-sm hover:from-amber-600 hover:via-pink-700 hover:to-purple-700 transition-all shrink-0"
                    >
                        <span>✨</span> {{ __('AI Generate Episode with Template') }}
                    </button>
                </div>

                <form wire:submit="createStory" class="space-y-4">
                    <div>
                        <flux:input wire:model="storyTitle" :label="__('Story / Episode Title')" placeholder="e.g., Episode 1: Awakening in Shibuya Station" />
                        @error('storyTitle') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <flux:textarea 
                            wire:model="narrativeScript" 
                            :label="__('Episode Narrative Script')" 
                            placeholder="{{ __('Paste the episode narrative text or chapter here. Gemini will break it down into sequential scenes matching your World Bible locations and characters...') }}" 
                            rows="6"
                        />
                        @error('narrativeScript') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <!-- Character Selection -->
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                            {{ __('Characters Appearing in this Story') }}
                        </label>
                        @if ($allCharacters->isEmpty())
                            <p class="text-xs text-zinc-400">{{ __('No characters in library yet.') }}</p>
                        @else
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 md:grid-cols-3">
                                @foreach ($allCharacters as $character)
                                    <label class="flex cursor-pointer items-start gap-3 rounded-lg border p-3 transition-all hover:bg-zinc-50 dark:hover:bg-zinc-800/50 {{ in_array($character->id, $selectedCharacterIds) ? 'border-blue-500 bg-blue-50/30 dark:border-blue-500 dark:bg-blue-950/20' : 'border-zinc-200 dark:border-zinc-700' }}">
                                        <input type="checkbox" wire:model.live="selectedCharacterIds" value="{{ $character->id }}" class="mt-1 rounded text-blue-600">
                                        <div class="overflow-hidden">
                                            <p class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ $character->name }}</p>
                                            <p class="text-xs text-zinc-500 truncate dark:text-zinc-400">{{ $character->prompt_keywords }}</p>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div class="flex justify-end pt-2">
                        <flux:button 
                            type="submit" 
                            variant="primary" 
                            wire:loading.attr="disabled"
                            class="bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white font-semibold shadow-sm"
                        >
                            <span wire:loading.remove wire:target="createStory" class="flex items-center gap-2">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                                {{ __('Breakdown Scenes with AI') }}
                            </span>
                            <span wire:loading wire:target="createStory" class="flex items-center gap-2">
                                <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                {{ __('Decomposing Episode Script...') }}
                            </span>
                        </flux:button>
                    </div>
                </form>
            </div>

            <!-- Stories List -->
            <div class="space-y-4">
                <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">
                    {{ __('Stories & Episodes under this Title') }} ({{ $contentTitle->stories->count() }})
                </h3>

                @if ($contentTitle->stories->isEmpty())
                    <div class="flex flex-col items-center justify-center rounded-xl border border-dashed border-zinc-300 p-12 text-center dark:border-zinc-700">
                        <span class="text-3xl block mb-2">🎬</span>
                        <h4 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ __('No stories created yet') }}</h4>
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Paste your first script above to start decomposing scenes.') }}</p>
                    </div>
                @else
                    <div class="grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-3">
                        @foreach ($contentTitle->stories as $story)
                            <div class="flex flex-col justify-between rounded-xl border border-zinc-200 bg-white p-5 shadow-sm transition-all hover:border-blue-400 dark:border-zinc-700 dark:bg-zinc-900">
                                <div class="space-y-3">
                                    <div class="flex items-start justify-between gap-2">
                                        <h4 class="font-bold text-zinc-900 line-clamp-1 dark:text-zinc-100">{{ $story->title }}</h4>
                                        <span class="shrink-0 rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $story->status === 'completed' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-400' : ($story->status === 'scenes_ready' ? 'bg-blue-100 text-blue-800 dark:bg-blue-950/40 dark:text-blue-400' : 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300') }}">
                                            {{ ucfirst(str_replace('_', ' ', $story->status)) }}
                                        </span>
                                    </div>

                                    <p class="text-xs text-zinc-500 line-clamp-2 dark:text-zinc-400">{{ $story->premise }}</p>

                                    <!-- Scene Stats -->
                                    @php
                                        $firstEpisode = $story->episodes->first();
                                        $totalBoards = $firstEpisode ? $firstEpisode->boards->count() : 0;
                                        $readyBoards = $firstEpisode ? $firstEpisode->boards->where('status', 'ready')->count() : 0;
                                    @endphp
                                    <div class="rounded-lg bg-zinc-50 p-2 text-xs text-zinc-600 dark:bg-zinc-800/50 dark:text-zinc-400 flex items-center justify-between">
                                        <span>{{ __('Scenes/Boards:') }} <strong>{{ $totalBoards }}</strong></span>
                                        <span>{{ __('Assets Ready:') }} <strong>{{ $readyBoards }}/{{ $totalBoards }}</strong></span>
                                    </div>
                                </div>

                                <div class="mt-4 flex items-center justify-between border-t border-zinc-100 pt-3 dark:border-zinc-800">
                                    <a 
                                        href="{{ route('story-studio.detail', $story->id) }}" 
                                        class="inline-flex items-center gap-1.5 text-xs font-semibold text-blue-600 hover:text-blue-700 dark:text-blue-400"
                                        wire:navigate
                                    >
                                        {{ __('Open Story Studio') }}
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </a>

                                    <button 
                                        wire:click="deleteStory({{ $story->id }})" 
                                        wire:confirm="{{ __('Delete this story?') }}"
                                        class="text-xs text-red-500 hover:text-red-700 dark:hover:text-red-400"
                                    >
                                        {{ __('Delete') }}
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    @endif

    <!-- ======================================================= -->
    <!-- UNIFIED DUAL-MODE ASSET CREATION / EDIT MODAL           -->
    <!-- ======================================================= -->
    @if ($showAssetModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-3 sm:p-5 backdrop-blur-md overflow-y-auto">
            <div class="w-full max-w-3xl max-h-[92vh] overflow-y-auto rounded-2xl border border-zinc-200 bg-white p-6 shadow-2xl dark:border-zinc-700 dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 space-y-5">
                
                <!-- Modal Header -->
                <div class="flex items-center justify-between border-b border-zinc-100 pb-3 dark:border-zinc-800">
                    <div>
                        <h3 class="text-base font-bold flex items-center gap-2">
                            <span>{{ $assetModalType === 'character' ? '👤' : ($assetModalType === 'environment' ? '🏞️' : '⚔️') }}</span>
                            {{ $editingAssetId ? __('Edit Asset Specifications') : ($assetModalType === 'character' ? __('Custom Character & Creature Model Sheet Creator') : ($assetModalType === 'environment' ? __('Create Environment / Location') : __('Create Prop / Gear'))) }}
                        </h3>
                        <p class="text-xs text-zinc-500">
                            {{ $assetModalType === 'character' ? __('Configure all facial, anatomy, outfit, or beast attributes yourself. Zero unwanted AI text injected.') : __('Choose whether to generate the model sheet from prompt or upload your prepared sheet.') }}
                        </p>
                    </div>
                    <button wire:click="$set('showAssetModal', false)" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <!-- Method Selector Tabs -->
                <div class="grid grid-cols-2 gap-3 rounded-xl bg-zinc-100 p-1.5 dark:bg-zinc-800 text-xs font-bold">
                    <button 
                        type="button" 
                        wire:click="$set('creationMode', 'prompt')"
                        class="flex items-center justify-center gap-2 rounded-lg py-2 transition-all {{ $creationMode === 'prompt' ? 'bg-white text-zinc-900 shadow-sm dark:bg-zinc-700 dark:text-white' : 'text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200' }}"
                    >
                        <span>🎨</span> {{ __('Method 1: Custom Attribute Builder & AI Sheet') }}
                    </button>
                    <button 
                        type="button" 
                        wire:click="$set('creationMode', 'upload')"
                        class="flex items-center justify-center gap-2 rounded-lg py-2 transition-all {{ $creationMode === 'upload' ? 'bg-white text-zinc-900 shadow-sm dark:bg-zinc-700 dark:text-white' : 'text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200' }}"
                    >
                        <span>📁</span> {{ __('Method 2: Direct Upload Prepared Sheet') }}
                    </button>
                </div>

                <form wire:submit="saveAsset" class="space-y-5 text-xs">
                    <!-- =============================================== -->
                    <!-- CHARACTER & CREATURE GRANULAR BUILDER           -->
                    <!-- =============================================== -->
                    @if ($assetModalType === 'character')
                        <!-- Row 1: Identity & Entity Type -->
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-4">
                            <div class="sm:col-span-2">
                                <flux:input wire:model.live.debounce.300ms="charName" :label="__('Character / Creature Name')" placeholder="e.g., Naya Arunika or Ironclaw Bear" />
                                @error('charName') <p class="text-red-500 mt-0.5">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <flux:select wire:model.live="charEntityType" :label="__('Entity Category')">
                                    <flux:select.option value="human">👤 {{ __('Human / Humanoid') }}</flux:select.option>
                                    <flux:select.option value="creature_beast">🐻 {{ __('Creature / Beast / Animal') }}</flux:select.option>
                                    <flux:select.option value="android_mech">🤖 {{ __('Android / Mech / Cyborg') }}</flux:select.option>
                                    <flux:select.option value="monster_demon">👹 {{ __('Monster / Alien / Demon') }}</flux:select.option>
                                </flux:select>
                            </div>
                            <div>
                                <flux:select wire:model.live="charGender" :label="__('Gender / Form')">
                                    <flux:select.option value="">{{ __('Unspecified / Optional') }}</flux:select.option>
                                    <flux:select.option value="female">{{ __('Female') }}</flux:select.option>
                                    <flux:select.option value="male">{{ __('Male') }}</flux:select.option>
                                    <flux:select.option value="androgynous">{{ __('Androgynous') }}</flux:select.option>
                                    <flux:select.option value="non_gendered">{{ __('Non-gendered / Beast') }}</flux:select.option>
                                </flux:select>
                            </div>
                        </div>

                        <!-- Row 2: Anatomy, Age, Build, Skin/Surface -->
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                            @if ($charEntityType === 'human')
                                <div>
                                    <flux:input type="number" wire:model.live.debounce.300ms="charAge" :label="__('Age (Years)')" placeholder="23" />
                                </div>
                            @endif
                            <div class="{{ $charEntityType === 'human' ? 'sm:col-span-1' : 'sm:col-span-1' }}">
                                <flux:input wire:model.live.debounce.300ms="charBodyBuild" :label="__('Body Silhouette & Build')" placeholder="e.g., Feminine curvy silhouette, athletic proportions" />
                                <div class="flex flex-wrap gap-1 mt-1">
                                    <button type="button" wire:click="appendSuggestionTag('charBodyBuild', 'Athletic and agile')" class="rounded px-1.5 py-0.5 text-[10px] bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300">+ Athletic</button>
                                    <button type="button" wire:click="appendSuggestionTag('charBodyBuild', 'Curvy feminine silhouette')" class="rounded px-1.5 py-0.5 text-[10px] bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300">+ Curvy</button>
                                    <button type="button" wire:click="appendSuggestionTag('charBodyBuild', 'Massive muscular frame')" class="rounded px-1.5 py-0.5 text-[10px] bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300">+ Muscular</button>
                                </div>
                            </div>
                            <div class="{{ $charEntityType === 'human' ? 'sm:col-span-1' : 'sm:col-span-2' }}">
                                <flux:input wire:model.live.debounce.300ms="charSkinMaterial" :label="__('Skin Complexion / Surface Material')" placeholder="e.g., Fair warm-toned Indonesian skin with natural texture" />
                                <div class="flex flex-wrap gap-1 mt-1">
                                    <button type="button" wire:click="appendSuggestionTag('charSkinMaterial', 'Fair warm-toned Indonesian skin')" class="rounded px-1.5 py-0.5 text-[10px] bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300">+ Warm Fair Skin</button>
                                    <button type="button" wire:click="appendSuggestionTag('charSkinMaterial', 'Matte charcoal plating')" class="rounded px-1.5 py-0.5 text-[10px] bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300">+ Charcoal Plating</button>
                                </div>
                            </div>
                        </div>

                        <!-- ADAPTIVE SECTION 1: HUMANOID / HUMAN -->
                        @if ($charEntityType === 'human')
                            <div class="rounded-xl border border-purple-200/80 bg-purple-50/20 p-4 dark:border-purple-900/40 dark:bg-purple-950/10 space-y-3">
                                <h4 class="text-xs font-bold text-purple-900 dark:text-purple-300 uppercase tracking-wider flex items-center gap-1.5">
                                    <span>👤</span> {{ __('Head, Hair & Facial Features') }}
                                </h4>
                                
                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                    <div>
                                        <flux:input wire:model.live.debounce.300ms="charHairStyle" :label="__('Hair Style & Cut')" placeholder="e.g., Chin-to-neck length dark espresso bob haircut with curved ends" />
                                        <div class="flex flex-wrap gap-1 mt-1">
                                            <button type="button" wire:click="appendSuggestionTag('charHairStyle', 'Chin-length dark bob haircut')" class="rounded px-1.5 py-0.5 text-[10px] bg-purple-100 dark:bg-purple-900/50 text-purple-800 dark:text-purple-300">+ Bob Cut</button>
                                            <button type="button" wire:click="appendSuggestionTag('charHairStyle', 'Messy anime layered spiky hair')" class="rounded px-1.5 py-0.5 text-[10px] bg-purple-100 dark:bg-purple-900/50 text-purple-800 dark:text-purple-300">+ Messy Spiky</button>
                                            <button type="button" wire:click="appendSuggestionTag('charHairStyle', 'Long straight flowing hair with bangs')" class="rounded px-1.5 py-0.5 text-[10px] bg-purple-100 dark:bg-purple-900/50 text-purple-800 dark:text-purple-300">+ Long Bangs</button>
                                        </div>
                                    </div>
                                    <div>
                                        <flux:input wire:model.live.debounce.300ms="charHairColor" :label="__('Hair Color')" placeholder="e.g., Dark espresso brown / black" />
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                    <div>
                                        <flux:input wire:model.live.debounce.300ms="charEyes" :label="__('Eyes & Eyebrows')" placeholder="e.g., Expressive deep brown eyes, soft naturally arched eyebrows" />
                                    </div>
                                    <div>
                                        <flux:input wire:model.live.debounce.300ms="charFacialFeatures" :label="__('Face Structure & Expression')" placeholder="e.g., Proportional oval face, gentle confident smile, subtle pores" />
                                    </div>
                                </div>
                            </div>

                            <!-- Humanoid Outfits -->
                            <div class="rounded-xl border border-blue-200/80 bg-blue-50/20 p-4 dark:border-blue-900/40 dark:bg-blue-950/10 space-y-3">
                                <h4 class="text-xs font-bold text-blue-900 dark:text-blue-300 uppercase tracking-wider flex items-center gap-1.5">
                                    <span>👕</span> {{ __('Outfit & Footwear') }}
                                </h4>
                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                                    <div>
                                        <flux:input wire:model.live.debounce.300ms="charUpperWear" :label="__('Upper Bodywear / Top')" placeholder="e.g., Sage green spaghetti-strap tank top" />
                                        <div class="flex flex-wrap gap-1 mt-1">
                                            <button type="button" wire:click="appendSuggestionTag('charUpperWear', 'Sage green spaghetti-strap tank top')" class="rounded px-1.5 py-0.5 text-[10px] bg-blue-100 dark:bg-blue-900/50 text-blue-800 dark:text-blue-300">+ Tank Top</button>
                                            <button type="button" wire:click="appendSuggestionTag('charUpperWear', 'Oversized purple tech hoodie')" class="rounded px-1.5 py-0.5 text-[10px] bg-blue-100 dark:bg-blue-900/50 text-blue-800 dark:text-blue-300">+ Tech Hoodie</button>
                                        </div>
                                    </div>
                                    <div>
                                        <flux:input wire:model.live.debounce.300ms="charLowerWear" :label="__('Lower Bodywear / Pants')" placeholder="e.g., High-waisted blue denim shorts" />
                                        <div class="flex flex-wrap gap-1 mt-1">
                                            <button type="button" wire:click="appendSuggestionTag('charLowerWear', 'High-waisted blue denim shorts')" class="rounded px-1.5 py-0.5 text-[10px] bg-blue-100 dark:bg-blue-900/50 text-blue-800 dark:text-blue-300">+ Denim Shorts</button>
                                            <button type="button" wire:click="appendSuggestionTag('charLowerWear', 'Charcoal cargo pants with straps')" class="rounded px-1.5 py-0.5 text-[10px] bg-blue-100 dark:bg-blue-900/50 text-blue-800 dark:text-blue-300">+ Cargo Pants</button>
                                        </div>
                                    </div>
                                    <div>
                                        <flux:input wire:model.live.debounce.300ms="charFootwear" :label="__('Footwear')" placeholder="e.g., Black and white classic skate sneakers" />
                                        <div class="flex flex-wrap gap-1 mt-1">
                                            <button type="button" wire:click="appendSuggestionTag('charFootwear', 'Classic skate sneakers')" class="rounded px-1.5 py-0.5 text-[10px] bg-blue-100 dark:bg-blue-900/50 text-blue-800 dark:text-blue-300">+ Sneakers</button>
                                            <button type="button" wire:click="appendSuggestionTag('charFootwear', 'Tactical combat boots')" class="rounded px-1.5 py-0.5 text-[10px] bg-blue-100 dark:bg-blue-900/50 text-blue-800 dark:text-blue-300">+ Combat Boots</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- ADAPTIVE SECTION 2: CREATURE / BEAST / MONSTER -->
                        @if (in_array($charEntityType, ['creature_beast', 'monster_demon']))
                            <div class="rounded-xl border border-amber-200/80 bg-amber-50/20 p-4 dark:border-amber-900/40 dark:bg-amber-950/10 space-y-3">
                                <h4 class="text-xs font-bold text-amber-900 dark:text-amber-300 uppercase tracking-wider flex items-center gap-1.5">
                                    <span>🐻</span> {{ __('Beast / Monster Anatomy & Features') }}
                                </h4>
                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                    <div>
                                        <flux:input wire:model.live.debounce.300ms="charHeadMuzzle" :label="__('Skull / Muzzle / Horns')" placeholder="e.g., Broad heavy ursine skull, curved bone horns" />
                                    </div>
                                    <div>
                                        <flux:input wire:model.live.debounce.300ms="charFurScales" :label="__('Fur / Mane / Scales / Chitin')" placeholder="e.g., Dense shaggy charcoal-black fur with frost guard hairs" />
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                                    <div>
                                        <flux:input wire:model.live.debounce.300ms="charEyesFangs" :label="__('Eyes & Fangs')" placeholder="e.g., Piercing glowing amber slit eyes, sharp canine fangs" />
                                    </div>
                                    <div>
                                        <flux:input wire:model.live.debounce.300ms="charPawsClaws" :label="__('Paws / Claws / Talons')" placeholder="e.g., Heavy paws with long razor-sharp black steel claws" />
                                    </div>
                                    <div>
                                        <flux:input wire:model.live.debounce.300ms="charCollarHarness" :label="__('Collar / Harness / Armor')" placeholder="e.g., Iron-studded leather neck collar with steel ring" />
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- ADAPTIVE SECTION 3: ANDROID / MECH -->
                        @if ($charEntityType === 'android_mech')
                            <div class="rounded-xl border border-cyan-200/80 bg-cyan-50/20 p-4 dark:border-cyan-900/40 dark:bg-cyan-950/10 space-y-3">
                                <h4 class="text-xs font-bold text-cyan-900 dark:text-cyan-300 uppercase tracking-wider flex items-center gap-1.5">
                                    <span>🤖</span> {{ __('Cybernetic / Mech Specifications') }}
                                </h4>
                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                    <div>
                                        <flux:input wire:model.live.debounce.300ms="charHeadVisor" :label="__('Head / Visor / Faceplate')" placeholder="e.g., Carbon fiber helmet with glowing horizontal neon cyan visor" />
                                    </div>
                                    <div>
                                        <flux:input wire:model.live.debounce.300ms="charPlatingChassis" :label="__('Armor Plating & Chassis')" placeholder="e.g., Matte titanium armor plates, exposed hydraulic neck cables" />
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                                    <div>
                                        <flux:input wire:model.live.debounce.300ms="charSensorsLights" :label="__('LED Circuits & Sensors')" placeholder="e.g., Pulsing purple LED circuit traces along collarbone" />
                                    </div>
                                    <div>
                                        <flux:input wire:model.live.debounce.300ms="charTorsoArmor" :label="__('Torso Chassis / Core')" placeholder="e.g., Reinforced chestplate with central glowing core" />
                                    </div>
                                    <div>
                                        <flux:input wire:model.live.debounce.300ms="charLimbsJoints" :label="__('Limbs & Thrusters')" placeholder="e.g., High-torque bionic limbs, micro-thrusters" />
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- Accessories & Color Swatches -->
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-4">
                            <div class="sm:col-span-2">
                                <flux:input wire:model.live.debounce.300ms="charAccessories" :label="__('Signature Accessories & Jewelry')" placeholder="e.g., Delicate silver water-drop necklace, small silver stud earrings, minimal wristwatch" />
                            </div>
                            <div>
                                <flux:input wire:model.live.debounce.300ms="charColorPrimary" :label="__('Primary Color')" placeholder="e.g., Sage Green / Lavender" />
                            </div>
                            <div>
                                <flux:input wire:model.live.debounce.300ms="charColorAccent" :label="__('Accent / Glow Color')" placeholder="e.g., Silver / Neon Cyan" />
                            </div>
                        </div>

                        <!-- Turnaround Options Switches -->
                        <div class="flex flex-wrap items-center gap-4 rounded-xl bg-zinc-50 p-3 dark:bg-zinc-800/60 text-xs">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" wire:model.live="includeTurnaround" class="rounded text-purple-600">
                                <span class="font-semibold">{{ __('3-View Turnaround (Front, Side, Back)') }}</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" wire:model.live="includeExpressionInset" class="rounded text-purple-600">
                                <span>{{ __('Expression Chart Inset') }}</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" wire:model.live="includeItemInset" class="rounded text-purple-600">
                                <span>{{ __('Outfit/Item Insets') }}</span>
                            </label>
                        </div>

                        <!-- LIVE REAL-TIME EDITABLE PROMPT PREVIEW -->
                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between">
                                <label class="text-xs font-bold text-zinc-700 dark:text-zinc-300 flex items-center gap-1.5">
                                    <span>⚡</span> {{ __('Real-Time Compiled Model Sheet Prompt (100% Deterministic & Editable)') }}
                                </label>
                                <button type="button" wire:click="recompileCharacterPrompt" class="text-[11px] font-semibold text-purple-600 hover:text-purple-700 dark:text-purple-400">
                                    🔄 {{ __('Re-sync from Fields') }}
                                </button>
                            </div>
                            <flux:textarea 
                                wire:model="compiledPrompt" 
                                rows="3" 
                                class="font-mono text-xs text-purple-950 dark:text-purple-200 bg-purple-50/40 dark:bg-purple-950/20 border-purple-200 dark:border-purple-800"
                            />
                            <p class="text-[10px] text-zinc-400">
                                {{ __('Only non-empty attributes are included. You can edit this box directly right before clicking generate.') }}
                            </p>
                        </div>
                    @endif

                    <!-- =============================================== -->
                    <!-- ENVIRONMENT FIELDS                              -->
                    <!-- =============================================== -->
                    @if ($assetModalType === 'environment')
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                            <div class="sm:col-span-2">
                                <flux:input wire:model="envName" :label="__('Location / Set Name')" placeholder="e.g., Shibuya VJ Workstation Pod 3C" />
                                @error('envName') <p class="text-red-500">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <flux:select wire:model="envSettingType" :label="__('Setting Type')">
                                    <flux:select.option value="interior">{{ __('Interior') }}</flux:select.option>
                                    <flux:select.option value="exterior">{{ __('Exterior') }}</flux:select.option>
                                    <flux:select.option value="urban">{{ __('Urban / Cyberpunk') }}</flux:select.option>
                                    <flux:select.option value="fantasy">{{ __('Fantasy Forest / Dungeon') }}</flux:select.option>
                                    <flux:select.option value="sci-fi">{{ __('Sci-Fi / Space') }}</flux:select.option>
                                </flux:select>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <flux:select wire:model="envTimeOfDay" :label="__('Time of Day')">
                                    <flux:select.option value="night">{{ __('Night / Neon') }}</flux:select.option>
                                    <flux:select.option value="daylight">{{ __('Daylight / Morning') }}</flux:select.option>
                                    <flux:select.option value="sunset">{{ __('Sunset / Golden Hour') }}</flux:select.option>
                                </flux:select>
                            </div>
                            <div>
                                <flux:input wire:model="envLighting" :label="__('Lighting & Atmosphere')" placeholder="e.g., Volumetric purple neon, screen glow" />
                            </div>
                        </div>

                        <div>
                            <flux:textarea 
                                wire:model="envPrompt" 
                                :label="__('Environment Concept Prompt / Keywords')" 
                                placeholder="e.g., Pod 3C workstation desk with triple monitors, VJ mixer controller, overlooking neon Shibuya night skyline through panoramic window" 
                                rows="3" 
                            />
                            @error('envPrompt') <p class="text-red-500">{{ $message }}</p> @enderror
                        </div>
                    @endif

                    <!-- =============================================== -->
                    <!-- PROP FIELDS                                     -->
                    <!-- =============================================== -->
                    @if ($assetModalType === 'prop')
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                            <div class="sm:col-span-2">
                                <flux:input wire:model="propName" :label="__('Prop / Gear Name')" placeholder="e.g., Tactical Roll-Top Backpack" />
                                @error('propName') <p class="text-red-500">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <flux:select wire:model="propCategory" :label="__('Category')">
                                    <flux:select.option value="gear">{{ __('Gear / Tech Backpack') }}</flux:select.option>
                                    <flux:select.option value="weapon">{{ __('Weapon / Combat') }}</flux:select.option>
                                    <flux:select.option value="artifact">{{ __('Artifact / Relic') }}</flux:select.option>
                                    <flux:select.option value="vehicle">{{ __('Vehicle') }}</flux:select.option>
                                </flux:select>
                            </div>
                        </div>

                        <div>
                            <flux:textarea 
                                wire:model="propPrompt" 
                                :label="__('Prop Design Prompt / Keywords')" 
                                placeholder="e.g., Tactical roll-top backpack, charcoal nylon, purple trim accents, MOLLE webbing, laptop compartment" 
                                rows="3" 
                            />
                            @error('propPrompt') <p class="text-red-500">{{ $message }}</p> @enderror
                        </div>
                    @endif

                    <!-- FILE UPLOAD DROPZONE (If Method 2 Selected) -->
                    @if ($creationMode === 'upload')
                        <div class="rounded-xl border-2 border-dashed border-purple-300 p-5 text-center dark:border-purple-800 bg-purple-50/20 dark:bg-purple-950/10">
                            <span class="text-3xl block mb-2">📁</span>
                            <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-1">
                                {{ __('Select Model Reference Sheet Image (PNG / JPG / WebP up to 15MB)') }}
                            </label>
                            <p class="text-[11px] text-zinc-400 mb-3">{{ __('Gemini Multimodal Vision will instantly deconstruct 3-views, expressions, color swatches, and materials upon saving.') }}</p>
                            <input type="file" wire:model="modalUploadFile" class="text-xs mx-auto block text-zinc-500">
                            @error('modalUploadFile') <p class="text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                    @endif

                    <!-- Modal Actions Footer -->
                    <div class="flex justify-end gap-2 border-t border-zinc-100 pt-4 dark:border-zinc-800">
                        <flux:button type="button" wire:click="$set('showAssetModal', false)" variant="ghost">
                            {{ __('Cancel') }}
                        </flux:button>
                        <flux:button type="submit" variant="primary" wire:loading.attr="disabled" class="bg-gradient-to-r from-purple-600 to-blue-600 hover:from-purple-700 hover:to-blue-700 text-white font-bold shadow-sm">
                            <span wire:loading.remove wire:target="saveAsset">
                                {{ $creationMode === 'prompt' ? __('Generate & Save Model Sheet') : __('Upload & Save Sheet') }}
                            </span>
                            <span wire:loading wire:target="saveAsset">
                                {{ __('Processing Model Sheet...') }}
                            </span>
                        </flux:button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- ======================================================= -->
    <!-- INSPECT REFERENCE SHEET MODAL                           -->
    <!-- ======================================================= -->
    @if ($showSheetModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4 backdrop-blur-md overflow-y-auto">
            <div class="w-full max-w-4xl max-h-[90vh] overflow-y-auto rounded-2xl border border-zinc-700 bg-zinc-900 p-6 shadow-2xl text-zinc-100 space-y-6">
                <!-- Modal Header -->
                <div class="flex items-center justify-between border-b border-zinc-800 pb-3">
                    <div>
                        <h3 class="text-lg font-bold flex items-center gap-2">
                            <span>📐</span> {{ $inspectingSheetTitle }}
                        </h3>
                        <p class="text-xs text-zinc-400">{{ __('Gemini Multimodal Vision Deconstructed Reference Sheet & Consistency Blueprint') }}</p>
                    </div>
                    <button wire:click="$set('showSheetModal', false)" class="text-zinc-400 hover:text-zinc-200">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <!-- High-Resolution Sheet Display -->
                @if ($inspectingSheetImageUrl)
                    <div class="overflow-hidden rounded-xl border border-zinc-800 bg-black aspect-video max-h-96 flex items-center justify-center shadow-inner">
                        <img src="{{ $inspectingSheetImageUrl }}" alt="{{ $inspectingSheetTitle }}" class="h-full w-full object-contain">
                    </div>
                @endif

                <!-- Deconstructed Breakdown -->
                @if ($inspectingSheetData)
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 text-xs">
                        <!-- Three Views Turnaround -->
                        @if (! empty($inspectingSheetData['three_views']))
                            <div class="rounded-xl border border-zinc-800 bg-zinc-950 p-4 space-y-2">
                                <h4 class="font-bold text-purple-400 uppercase tracking-wider text-[11px] flex items-center gap-1.5">
                                    <span>🔄</span> {{ __('Three Views Turnaround') }}
                                </h4>
                                <div class="space-y-1.5 text-zinc-300">
                                    <p><strong>Front:</strong> {{ $inspectingSheetData['three_views']['front'] ?? 'N/A' }}</p>
                                    <p><strong>Side:</strong> {{ $inspectingSheetData['three_views']['side'] ?? 'N/A' }}</p>
                                    <p><strong>Back:</strong> {{ $inspectingSheetData['three_views']['back'] ?? 'N/A' }}</p>
                                </div>
                            </div>
                        @endif

                        <!-- Environment Perspectives -->
                        @if (! empty($inspectingSheetData['main_view']))
                            <div class="rounded-xl border border-zinc-800 bg-zinc-950 p-4 space-y-2">
                                <h4 class="font-bold text-emerald-400 uppercase tracking-wider text-[11px] flex items-center gap-1.5">
                                    <span>🏞️</span> {{ __('Environment Layout') }}
                                </h4>
                                <div class="space-y-1.5 text-zinc-300">
                                    <p><strong>Main View:</strong> {{ $inspectingSheetData['main_view'] }}</p>
                                    @if (! empty($inspectingSheetData['exterior_view']))
                                        <p><strong>Exterior:</strong> {{ $inspectingSheetData['exterior_view'] }}</p>
                                    @endif
                                    @if (! empty($inspectingSheetData['time_of_day_variants']))
                                        <p><strong>Day/Night:</strong> {{ json_encode($inspectingSheetData['time_of_day_variants']) }}</p>
                                    @endif
                                </div>
                            </div>
                        @endif

                        <!-- Color Palette Swatches -->
                        @if (! empty($inspectingSheetData['color_palette']))
                            <div class="rounded-xl border border-zinc-800 bg-zinc-950 p-4 space-y-2">
                                <h4 class="font-bold text-blue-400 uppercase tracking-wider text-[11px] flex items-center gap-1.5">
                                    <span>🎨</span> {{ __('Color Palette Swatches') }}
                                </h4>
                                <div class="flex flex-wrap gap-2 pt-1">
                                    @foreach ($inspectingSheetData['color_palette'] as $swatch)
                                        <div class="rounded-lg border border-zinc-800 bg-zinc-900 px-2.5 py-1 text-[11px]">
                                            <span class="text-zinc-400">{{ is_array($swatch) ? ($swatch['name'] ?? '') : 'Color' }}:</span>
                                            <span class="font-bold text-white">{{ is_array($swatch) ? ($swatch['color'] ?? '') : $swatch }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <!-- Materials & Textures -->
                        @if (! empty($inspectingSheetData['materials']) || ! empty($inspectingSheetData['materials_and_textures']))
                            <div class="rounded-xl border border-zinc-800 bg-zinc-950 p-4 space-y-2">
                                <h4 class="font-bold text-amber-400 uppercase tracking-wider text-[11px] flex items-center gap-1.5">
                                    <span>🧵</span> {{ __('Materials & Surface Textures') }}
                                </h4>
                                <p class="text-zinc-300 leading-relaxed">
                                    {{ $inspectingSheetData['materials'] ?? $inspectingSheetData['materials_and_textures'] }}
                                </p>
                            </div>
                        @endif

                        <!-- Consistency Prompt Tokens -->
                        @if (! empty($inspectingSheetData['consistency_prompt_tokens']))
                            <div class="col-span-full rounded-xl border border-purple-500/40 bg-purple-950/20 p-4 space-y-2">
                                <div class="flex items-center justify-between">
                                    <h4 class="font-bold text-purple-300 uppercase tracking-wider text-[11px] flex items-center gap-1.5">
                                        <span>🔒</span> {{ __('Concentrated Consistency Prompt Tokens') }}
                                    </h4>
                                    <span class="text-[10px] text-purple-400 font-mono">{{ __('Injected into Storyboard Generation') }}</span>
                                </div>
                                <div class="rounded-lg bg-black/60 p-3 font-mono text-xs text-purple-200 border border-purple-900/50 select-all">
                                    {{ $inspectingSheetData['consistency_prompt_tokens'] }}
                                </div>
                            </div>
                        @endif
                    </div>
                @endif

                <div class="flex justify-end border-t border-zinc-800 pt-3">
                    <button wire:click="$set('showSheetModal', false)" class="rounded-xl bg-zinc-800 px-5 py-2 text-xs font-bold text-white hover:bg-zinc-700">
                        {{ __('Close Inspector') }}
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Edit Content Title Modal -->
    <flux:modal wire:model="showEditTitleModal" class="max-w-2xl">
        @if ($showEditTitleModal)
            <form wire:submit="saveTitleSettings" class="space-y-4">
                <div>
                    <flux:heading size="lg">{{ __('Edit Content Title Settings') }}</flux:heading>
                    <flux:subheading>{{ __('Update series worldbuilding, aspect ratio, styles, and model transporters.') }}</flux:subheading>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <flux:input wire:model="editTitle" :label="__('Content / Series Title')" required />
                        @error('editTitle') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <flux:select wire:model="editAspectRatio" :label="__('Default Aspect Ratio')">
                            <flux:select.option value="9:16">9:16 Vertical (TikTok / Shorts)</flux:select.option>
                            <flux:select.option value="16:9">16:9 Landscape (YouTube)</flux:select.option>
                        </flux:select>
                    </div>
                </div>

                <div>
                    <flux:textarea wire:model="editDescription" :label="__('Series Premise / World Description')" rows="3" />
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <flux:select wire:model="editImageModelConnectionId" :label="__('AI Image Model')">
                            <flux:select.option value="">{{ __('Default Configured Model') }}</flux:select.option>
                            @foreach ($availableImageModels as $model)
                                <flux:select.option value="{{ $model->id }}">
                                    {{ $model->name }} {{ $model->is_default ? '★' : '' }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>

                    <div>
                        <flux:select wire:model="editTextModelConnectionId" :label="__('AI Text LLM Model')">
                            <flux:select.option value="">{{ __('Default Configured LLM') }}</flux:select.option>
                            @foreach ($availableTextModels as $model)
                                <flux:select.option value="{{ $model->id }}">
                                    {{ $model->name }} {{ $model->is_default ? '★' : '' }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 mt-2">
                    <div class="col-span-full pt-4 border-t border-zinc-100 dark:border-zinc-800">
                        <flux:heading size="lg">{{ __('AI Image Prompt Configuration (Brain Key)') }}</flux:heading>
                        <flux:subheading>{{ __('Set the foundational artistic direction applied to all generated images for this title.') }}</flux:subheading>
                    </div>

                    <div>
                        <flux:select wire:model.live="promptLevel1" :label="__('Level 1: Base Art Style')">
                            @foreach (array_keys($promptTree) as $style)
                                <flux:select.option value="{{ $style }}">{{ $style }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>

                    <div>
                        @if(!empty($promptLevel1) && isset($promptTree[$promptLevel1]))
                        <flux:select wire:model="promptLevel2" :label="__('Level 2: Sub-Genre & Setting')">
                            @foreach ($promptTree[$promptLevel1]['level2'] as $subGenre)
                                <flux:select.option value="{{ $subGenre }}">{{ $subGenre }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        @endif
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 mt-2">
                    <div class="col-span-full">
                        <flux:input wire:model="promptLevel5" :label="__('Level 5: Artistic Modifiers')" placeholder="masterpiece, 8k, highly detailed..." />
                    </div>
                </div>

                <div class="col-span-full mt-2 p-3 bg-zinc-50 dark:bg-zinc-800 rounded-lg">
                    <div class="text-xs text-zinc-500 uppercase tracking-wider mb-1">{{ __('Compiled Base Formula Preview') }}</div>
                    <div class="text-sm font-medium text-zinc-900 dark:text-white">
                        {{ implode(', ', array_filter([$promptLevel1, $promptLevel2, $promptLevel5])) }}
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 mt-4">

                    <div>
                        <flux:select wire:model="editVoiceName" :label="__('Default Narrator')">
                            @foreach ($voices as $voiceKey => $voiceLabel)
                                <flux:select.option value="{{ $voiceKey }}">{{ $voiceLabel }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-zinc-100 dark:border-zinc-800">
                    <flux:button type="button" variant="ghost" wire:click="closeEditTitleModal">
                        {{ __('Cancel') }}
                    </flux:button>
                    <flux:button type="submit" variant="primary">
                        {{ __('Save Changes') }}
                    </flux:button>
                </div>
            </form>
        @endif
    </flux:modal>

    <!-- AI World Bible Generator Modal -->
    <flux:modal wire:model="showAiWorldBibleModal" class="max-w-2xl">
        @if ($showAiWorldBibleModal)
            <div class="space-y-4">
                <div>
                    <flux:heading size="lg" class="flex items-center gap-2">
                        <span>✨</span> {{ __('AI World Bible Generator') }}
                    </flux:heading>
                    <flux:subheading>
                        {{ __('Generate lore-consistent characters, environments, and props matching your chosen template and title aesthetic.') }}
                    </flux:subheading>
                </div>

                <!-- Template Selector -->
                <div>
                    <flux:select wire:model="aiBibleTemplateId" :label="__('Content Factory Template Tone & Archetypes')">
                        <flux:select.option value="">{{ __('Auto-detect from Title Genre & Visual Style') }}</flux:select.option>
                        @foreach ($templates as $tmpl)
                            <flux:select.option value="{{ $tmpl->id }}">
                                {{ $tmpl->name }} ({{ $tmpl->genre }} • {{ $tmpl->visual_style }})
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <!-- Scope Selector -->
                <div>
                    <flux:select wire:model.live="aiBibleScope" :label="__('Generation Scope')">
                        <flux:select.option value="all">{{ __('Complete World Bible (Characters + Locations + Props)') }}</flux:select.option>
                        <flux:select.option value="characters">{{ __('Characters & Creatures Only') }}</flux:select.option>
                        <flux:select.option value="environments">{{ __('Locations & Environments Only') }}</flux:select.option>
                        <flux:select.option value="props">{{ __('Key Props & Gear Only') }}</flux:select.option>
                    </flux:select>
                </div>

                <!-- Quantities -->
                <div class="grid grid-cols-3 gap-3">
                    @if (in_array($aiBibleScope, ['all', 'characters']))
                        <div>
                            <flux:input type="number" min="1" max="5" wire:model="aiCharCount" :label="__('Characters')" />
                        </div>
                    @endif
                    @if (in_array($aiBibleScope, ['all', 'environments']))
                        <div>
                            <flux:input type="number" min="1" max="5" wire:model="aiEnvCount" :label="__('Locations')" />
                        </div>
                    @endif
                    @if (in_array($aiBibleScope, ['all', 'props']))
                        <div>
                            <flux:input type="number" min="1" max="5" wire:model="aiPropCount" :label="__('Props')" />
                        </div>
                    @endif
                </div>

                <!-- Creative Directives -->
                <div>
                    <flux:textarea 
                        wire:model="aiBiblePrompt" 
                        :label="__('Custom Directives & Story Premise (Optional)')" 
                        placeholder="{{ __('e.g., Include a quirky animal companion with an ancient relic, a secluded forest haven, and high-tech stealth gear...') }}" 
                        rows="3" 
                    />
                </div>

                <!-- Auto-generate Sheets Option -->
                <div class="rounded-xl border border-purple-200/60 bg-purple-50/40 p-3 dark:border-purple-950/40 dark:bg-purple-950/20">
                    <flux:checkbox 
                        wire:model="aiAutoGenerateSheets" 
                        :label="__('Auto-generate visual concept sheets immediately')" 
                        :description="__('Sends generation requests to the configured image model transporter right after asset creation.')"
                    />
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-zinc-100 dark:border-zinc-800">
                    <flux:button type="button" variant="ghost" wire:click="$set('showAiWorldBibleModal', false)">
                        {{ __('Cancel') }}
                    </flux:button>
                    <flux:button 
                        type="button" 
                        variant="primary" 
                        wire:click="generateWorldBibleWithAi"
                        wire:loading.attr="disabled"
                        class="bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white font-semibold"
                    >
                        <span wire:loading.remove wire:target="generateWorldBibleWithAi" class="flex items-center gap-1.5">
                            <span>✨</span> {{ __('Generate World Bible Assets') }}
                        </span>
                        <span wire:loading wire:target="generateWorldBibleWithAi" class="flex items-center gap-1.5">
                            <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            {{ __('Creating Assets with AI...') }}
                        </span>
                    </flux:button>
                </div>
            </div>
        @endif
    </flux:modal>

    <!-- AI Story Episode Generator Modal -->
    <flux:modal wire:model="showAiEpisodeModal" class="max-w-2xl">
        @if ($showAiEpisodeModal)
            <div class="space-y-4">
                <div>
                    <flux:heading size="lg" class="flex items-center gap-2">
                        <span>🎬</span> {{ __('AI Story Episode Generator') }}
                    </flux:heading>
                    <flux:subheading>
                        {{ __('Generate a full narrative script grounded in your World Bible lore, characters, and dramatic templates.') }}
                    </flux:subheading>
                </div>

                <!-- Template Selector -->
                <div>
                    <flux:select wire:model="aiEpisodeTemplateId" :label="__('Content Factory Template Tone')">
                        <flux:select.option value="">{{ __('Auto-detect from Title Genre & Style') }}</flux:select.option>
                        @foreach ($templates as $tmpl)
                            <flux:select.option value="{{ $tmpl->id }}">
                                {{ $tmpl->name }} ({{ $tmpl->genre }} • {{ $tmpl->visual_style }})
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <!-- Episode Hook / Premise -->
                <div>
                    <flux:textarea 
                        wire:model="aiEpisodeHook" 
                        :label="__('Episode Premise or Dramatic Hook (Optional)')" 
                        placeholder="{{ __('e.g., Episode 1: The discovery of an ancient map etched into a golden acorn that points toward the forbidden owl kingdom...') }}" 
                        rows="3" 
                    />
                </div>

                <!-- Pacing Selection -->
                <div>
                    <flux:select wire:model="aiEpisodePacing" :label="__('Episode Pacing & Scope')">
                        <flux:select.option value="short">{{ __('Short Teaser / Recap (1 - 2 mins, ~150-250 words)') }}</flux:select.option>
                        <flux:select.option value="standard">{{ __('Standard Episode (3 - 5 mins, ~350-600 words)') }}</flux:select.option>
                        <flux:select.option value="extended">{{ __('Extended Featurette (7 - 10 mins, ~700-1100 words)') }}</flux:select.option>
                    </flux:select>
                </div>

                <!-- Characters Included -->
                @if ($contentTitle->characters->isNotEmpty())
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                            {{ __('Participating Characters') }}
                        </label>
                        <div class="grid grid-cols-2 gap-2 max-h-40 overflow-y-auto p-2 rounded-xl border border-zinc-200 dark:border-zinc-800">
                            @foreach ($contentTitle->characters as $char)
                                <label class="flex items-center gap-2 text-xs text-zinc-700 dark:text-zinc-300 cursor-pointer p-1 rounded hover:bg-zinc-100 dark:hover:bg-zinc-800">
                                    <input type="checkbox" wire:model="aiEpisodeCharacterIds" value="{{ $char->id }}" class="rounded text-purple-600">
                                    <span class="font-medium truncate">{{ $char->name }}</span>
                                    <span class="text-[10px] text-zinc-400">({{ $char->role ?? 'Hero' }})</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Environments Included -->
                @if ($contentTitle->environments->isNotEmpty())
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                            {{ __('Featured Locations') }}
                        </label>
                        <div class="grid grid-cols-2 gap-2 max-h-40 overflow-y-auto p-2 rounded-xl border border-zinc-200 dark:border-zinc-800">
                            @foreach ($contentTitle->environments as $env)
                                <label class="flex items-center gap-2 text-xs text-zinc-700 dark:text-zinc-300 cursor-pointer p-1 rounded hover:bg-zinc-100 dark:hover:bg-zinc-800">
                                    <input type="checkbox" wire:model="aiEpisodeEnvironmentIds" value="{{ $env->id }}" class="rounded text-blue-600">
                                    <span class="font-medium truncate">{{ $env->name }}</span>
                                    <span class="text-[10px] text-zinc-400 capitalize">({{ $env->time_of_day ?? 'Day' }})</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-zinc-100 dark:border-zinc-800">
                    <flux:button type="button" variant="ghost" wire:click="$set('showAiEpisodeModal', false)">
                        {{ __('Cancel') }}
                    </flux:button>
                    <flux:button 
                        type="button" 
                        variant="primary" 
                        wire:click="generateEpisodeWithAi"
                        wire:loading.attr="disabled"
                        class="bg-gradient-to-r from-pink-600 via-purple-600 to-indigo-600 hover:from-pink-700 hover:to-indigo-700 text-white font-semibold"
                    >
                        <span wire:loading.remove wire:target="generateEpisodeWithAi" class="flex items-center gap-1.5">
                            <span>🎬</span> {{ __('Generate Episode & Decompose Scenes') }}
                        </span>
                        <span wire:loading wire:target="generateEpisodeWithAi" class="flex items-center gap-1.5">
                            <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            {{ __('Writing & Decomposing Episode...') }}
                        </span>
                    </flux:button>
                </div>
            </div>
        @endif
    </flux:modal>
</div>
