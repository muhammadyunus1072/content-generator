<div class="w-full space-y-6">
    <!-- Header with Tabs -->
    <div class="relative overflow-hidden rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="absolute -right-16 -top-16 h-32 w-32 rounded-full bg-blue-500/10 blur-3xl dark:bg-blue-400/10"></div>
        <div class="absolute -bottom-16 -left-16 h-32 w-32 rounded-full bg-purple-500/10 blur-3xl dark:bg-purple-400/10"></div>
        
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <flux:heading size="xl" level="1" class="mb-1 bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent font-bold dark:from-blue-400 dark:to-purple-400">
                    {{ __('Story Studio') }}
                </flux:heading>
                <flux:subheading>{{ __('Organize your video content by Content Titles, with shared character sheets and multi-episode stories.') }}</flux:subheading>
            </div>

            <!-- Tabs Nav -->
            <div class="flex items-center rounded-xl border border-zinc-200 bg-zinc-100/70 p-1 dark:border-zinc-800 dark:bg-zinc-800/60">
                <button 
                    wire:click="setTab('titles')"
                    class="flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium transition-all {{ $tab === 'titles' ? 'bg-white text-zinc-900 shadow-sm dark:bg-zinc-900 dark:text-white' : 'text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white' }}"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                    {{ __('Content Titles') }}
                </button>
                <button 
                    wire:click="setTab('characters')"
                    class="flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium transition-all {{ $tab === 'characters' ? 'bg-white text-zinc-900 shadow-sm dark:bg-zinc-900 dark:text-white' : 'text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white' }}"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    {{ __('Character Library') }}
                </button>
            </div>
        </div>
    </div>

    <!-- TAB 1: CONTENT TITLES -->
    @if ($tab === 'titles')
        <div class="space-y-6">
            <!-- Create New Content Title Card -->
            <div x-data="{ openCreate: {{ $openCreateForm ? 'true' : 'false' }} }" class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-center justify-between cursor-pointer" x-on:click="openCreate = !openCreate">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Create New Content Title') }}</h2>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Set up an overarching show or series title with shared worldbuilding, visual style, and recurring characters.') }}</p>
                        </div>
                    </div>
                    <button type="button" class="rounded-lg p-2 text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800">
                        <svg class="h-5 w-5 transition-transform duration-200" :class="openCreate ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                </div>

                <form wire:submit="createContentTitle" x-show="openCreate" x-cloak class="mt-6 space-y-5 border-t border-zinc-100 pt-6 dark:border-zinc-800">
                    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                        <!-- Content Title -->
                        <div>
                            <flux:input wire:model="title" :label="__('Content / Series Title')" placeholder="e.g., Solo Hunter Chronicles" />
                            @error('title') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>

                        <!-- Aspect Ratio -->
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">{{ __('Default Aspect Ratio') }}</label>
                            <div class="grid grid-cols-2 gap-3">
                                <label class="flex cursor-pointer items-center justify-between rounded-lg border p-3 text-sm transition-all {{ $aspectRatio === '9:16' ? 'border-blue-500 bg-blue-50/50 dark:border-blue-500 dark:bg-blue-950/20' : 'border-zinc-200 dark:border-zinc-700' }}">
                                    <div class="flex items-center gap-2">
                                        <input type="radio" wire:model.live="aspectRatio" value="9:16" class="text-blue-600">
                                        <span class="font-medium text-zinc-900 dark:text-zinc-100">9:16 Vertical</span>
                                    </div>
                                    <span class="text-xs text-zinc-500">TikTok / Shorts</span>
                                </label>
                                <label class="flex cursor-pointer items-center justify-between rounded-lg border p-3 text-sm transition-all {{ $aspectRatio === '16:9' ? 'border-blue-500 bg-blue-50/50 dark:border-blue-500 dark:bg-blue-950/20' : 'border-zinc-200 dark:border-zinc-700' }}">
                                    <div class="flex items-center gap-2">
                                        <input type="radio" wire:model.live="aspectRatio" value="16:9" class="text-blue-600">
                                        <span class="font-medium text-zinc-900 dark:text-zinc-100">16:9 Landscape</span>
                                    </div>
                                    <span class="text-xs text-zinc-500">YouTube</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Description -->
                    <div>
                        <flux:textarea 
                            wire:model="description" 
                            :label="__('Series Premise / World Description')" 
                            placeholder="{{ __('Briefly describe the theme, world, or lore for this content title...') }}" 
                            rows="3"
                        />
                    </div>

                    <!-- Character Selection -->
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                            {{ __('Recurring Characters') }}
                            <span class="text-xs text-zinc-500 font-normal">({{ __('Characters selected here will automatically be attached to stories under this title') }})</span>
                        </label>
                        @if ($characters->isEmpty())
                            <p class="text-xs text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/30 p-3 rounded-lg border border-amber-200 dark:border-amber-900">
                                {{ __('No characters in library yet. Add character sheets in the "Character Library" tab.') }}
                            </p>
                        @else
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 md:grid-cols-3">
                                @foreach ($characters as $character)
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

                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-4">
                        <!-- Image AI Model Selector -->
                        <div>
                            <flux:select wire:model="imageModelConnectionId" :label="__('AI Image Model')">
                                <flux:select.option value="">{{ __('Default Configured Model') }}</flux:select.option>
                                @foreach ($availableImageModels as $model)
                                    <flux:select.option value="{{ $model->id }}">
                                        {{ $model->name }} {{ $model->is_default ? '★' : '' }}
                                    </flux:select.option>
                                @endforeach
                            </flux:select>
                        </div>

                        <!-- Text AI Model Selector -->
                        <div>
                            <flux:select wire:model="textModelConnectionId" :label="__('AI Text LLM Model')">
                                <flux:select.option value="">{{ __('Default Configured LLM') }}</flux:select.option>
                                @foreach ($availableTextModels as $model)
                                    <flux:select.option value="{{ $model->id }}">
                                        {{ $model->name }} {{ $model->is_default ? '★' : '' }}
                                    </flux:select.option>
                                @endforeach
                            </flux:select>
                        </div>

                        <!-- Visual Style -->
                        <div>
                            <flux:select wire:model="visualStyle" :label="__('Default Visual Style')">
                                <flux:select.option value="Dark Fantasy Manhwa">Dark Fantasy Manhwa (Solo Leveling Style)</flux:select.option>
                                <flux:select.option value="Modern Anime Aesthetic">Modern Anime Aesthetic (Makoto Shinkai Style)</flux:select.option>
                                <flux:select.option value="Photorealistic Cinematic">Photorealistic Cinematic (Realistic 8K Portrait)</flux:select.option>
                                <flux:select.option value="Cyberpunk Neon">Cyberpunk Neon Webtoon</flux:select.option>
                                <flux:select.option value="Classic Comic Book">Classic Graphic Novel / Comic</flux:select.option>
                            </flux:select>
                        </div>

                        <!-- Voice Narrator -->
                        <div>
                            <flux:select wire:model="voiceName" :label="__('Narrator (Edge-TTS)')">
                                @foreach ($voices as $voiceKey => $voiceLabel)
                                    <flux:select.option value="{{ $voiceKey }}">{{ $voiceLabel }}</flux:select.option>
                                @endforeach
                            </flux:select>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex justify-end pt-2">
                        <flux:button type="submit" variant="primary" class="bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white font-semibold shadow-sm">
                            {{ __('Save Content Title') }}
                        </flux:button>
                    </div>
                </form>
            </div>

            <!-- Content Titles Grid -->
            <div>
                <h3 class="mb-4 text-base font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Your Content Titles') }}</h3>

                @if ($contentTitles->isEmpty())
                    <div class="flex flex-col items-center justify-center rounded-xl border border-dashed border-zinc-300 p-12 text-center dark:border-zinc-700">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-zinc-100 text-zinc-400 dark:bg-zinc-800">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                        </div>
                        <h4 class="mt-3 text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ __('No content titles yet') }}</h4>
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Create a Content Title above to group your story episodes.') }}</p>
                    </div>
                @else
                    <div class="grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-3">
                        @foreach ($contentTitles as $item)
                            <div class="flex flex-col justify-between rounded-xl border border-zinc-200 bg-white p-5 shadow-sm transition-all hover:border-blue-400 dark:border-zinc-700 dark:bg-zinc-900">
                                <div class="space-y-3">
                                    <div class="flex items-start justify-between gap-2">
                                        <h4 class="font-bold text-zinc-900 line-clamp-1 dark:text-zinc-100">{{ $item->title }}</h4>
                                        <span class="shrink-0 rounded-full px-2.5 py-0.5 text-xs font-semibold bg-blue-50 text-blue-700 dark:bg-blue-950/40 dark:text-blue-300">
                                            {{ $item->stories->count() }} {{ __('Stories') }}
                                        </span>
                                    </div>

                                    @if ($item->description)
                                        <p class="text-xs text-zinc-500 line-clamp-2 dark:text-zinc-400">{{ $item->description }}</p>
                                    @endif

                                    <!-- Badges -->
                                    <div class="flex flex-wrap gap-1.5 pt-1">
                                        <span class="rounded bg-zinc-100 px-2 py-0.5 text-[10px] font-medium text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                                            {{ $item->aspect_ratio }}
                                        </span>
                                        <span class="rounded bg-zinc-100 px-2 py-0.5 text-[10px] font-medium text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                                            {{ $item->visual_style }}
                                        </span>
                                        @foreach ($item->characters as $char)
                                            <span class="rounded bg-purple-50 px-2 py-0.5 text-[10px] font-medium text-purple-700 dark:bg-purple-950/40 dark:text-purple-300">
                                                👤 {{ $char->name }}
                                            </span>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="mt-4 flex items-center justify-between border-t border-zinc-100 pt-3 dark:border-zinc-800">
                                    <a 
                                        href="{{ route('story-studio.title', $item->id) }}" 
                                        class="inline-flex items-center gap-1.5 text-xs font-semibold text-blue-600 hover:text-blue-700 dark:text-blue-400"
                                        wire:navigate
                                    >
                                        {{ __('Manage Stories & Episodes') }}
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </a>

                                    <div class="flex items-center gap-3">
                                        <button 
                                            type="button"
                                            wire:click="openEditContentTitle({{ $item->id }})" 
                                            class="text-xs font-semibold text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-200"
                                        >
                                            {{ __('Edit') }}
                                        </button>

                                        <button 
                                            wire:click="deleteContentTitle({{ $item->id }})" 
                                            wire:confirm="{{ __('Delete this Content Title and all its stories?') }}"
                                            class="text-xs text-red-500 hover:text-red-700 dark:hover:text-red-400"
                                        >
                                            {{ __('Delete') }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    @endif

    <!-- TAB 2: CHARACTER LIBRARY -->
    @if ($tab === 'characters')
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Left: Character Form -->
            <div class="lg:col-span-1">
                <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100 mb-1">
                        {{ $editingCharacterId ? __('Edit Character Sheet') : __('Add New Character Sheet') }}
                    </h3>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-5">
                        {{ __('Define consistent appearance keywords to maintain face and clothing continuity in scenes.') }}
                    </p>

                    <form wire:submit="saveCharacter" class="space-y-4">
                        <div>
                            <flux:input wire:model="characterName" :label="__('Character Name')" placeholder="e.g., Naya Arunika" />
                            @error('characterName') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <flux:select wire:model="characterGender" :label="__('Gender')">
                                    <flux:select.option value="female">{{ __('Female') }}</flux:select.option>
                                    <flux:select.option value="male">{{ __('Male') }}</flux:select.option>
                                    <flux:select.option value="other">{{ __('Other') }}</flux:select.option>
                                </flux:select>
                            </div>
                            <div>
                                <flux:input type="number" wire:model="characterAge" :label="__('Age')" placeholder="23" />
                            </div>
                        </div>

                        <!-- Prompt Keywords (Critical for SDXL consistency) -->
                        <div>
                            <flux:textarea 
                                wire:model="characterPromptKeywords" 
                                :label="__('Prompt Keywords (SDXL tokens)')" 
                                placeholder="e.g., 23yo Indonesian woman, Sundanese Javanese, oval face, natural warm fair skin, dark espresso bob haircut, sage green spaghetti-strap tank top, denim shorts" 
                                rows="4"
                            />
                            <p class="mt-1 text-[11px] text-zinc-400">
                                {{ __('Concise visual tokens injected into image prompts when this character appears.') }}
                            </p>
                            @error('characterPromptKeywords') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>

                        <!-- Appearance Notes -->
                        <div>
                            <flux:textarea 
                                wire:model="characterAppearance" 
                                :label="__('Full Character Bio / Appearance Notes')" 
                                placeholder="e.g., Calm, cheerful, confident personality. Wears minimal silver stud earrings and delicate necklace." 
                                rows="3"
                            />
                        </div>

                        <div class="flex items-center gap-2 pt-2">
                            <flux:button type="submit" variant="primary" class="w-full">
                                {{ $editingCharacterId ? __('Update Character') : __('Save Character') }}
                            </flux:button>

                            @if ($editingCharacterId)
                                <flux:button type="button" wire:click="resetCharacterForm" variant="ghost">
                                    {{ __('Cancel') }}
                                </flux:button>
                            @endif
                        </div>
                    </form>
                </div>
            </div>

            <!-- Right: Character Cards Grid -->
            <div class="lg:col-span-2 space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">
                        {{ __('Character Sheets') }} ({{ $characters->count() }})
                    </h3>
                </div>

                @if ($characters->isEmpty())
                    <div class="flex flex-col items-center justify-center rounded-xl border border-dashed border-zinc-300 p-12 text-center dark:border-zinc-700">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-zinc-100 text-zinc-400 dark:bg-zinc-800">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                        <h4 class="mt-3 text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ __('No characters in library') }}</h4>
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Use the form on the left to add your first character sheet.') }}</p>
                    </div>
                @else
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        @foreach ($characters as $char)
                            <div class="flex flex-col justify-between rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                                <div class="space-y-3">
                                    <div class="flex items-start justify-between">
                                        <div>
                                            <h4 class="font-bold text-zinc-900 dark:text-zinc-100">{{ $char->name }}</h4>
                                            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                                {{ ucfirst($char->gender ?? 'Unspecified') }} • {{ $char->age ? $char->age . ' yrs' : 'Age unspecified' }}
                                            </p>
                                        </div>
                                        <div class="flex items-center gap-1">
                                            <button wire:click="editCharacter({{ $char->id }})" class="p-1 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                                </svg>
                                            </button>
                                            <button wire:click="deleteCharacter({{ $char->id }})" wire:confirm="{{ __('Delete character sheet?') }}" class="p-1 text-red-400 hover:text-red-600">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Prompt Keywords -->
                                    <div class="rounded-lg bg-zinc-50 p-3 dark:bg-zinc-800/60">
                                        <p class="text-[11px] font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-1">{{ __('Prompt Keywords:') }}</p>
                                        <p class="text-xs text-zinc-700 dark:text-zinc-300 font-mono">{{ $char->prompt_keywords }}</p>
                                    </div>

                                    @if ($char->appearance)
                                        <p class="text-xs text-zinc-500 line-clamp-2 dark:text-zinc-400 italic">"{{ $char->appearance }}"</p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    @endif

    <!-- Edit Content Title Modal -->
    <flux:modal wire:model="showEditTitleModal" class="max-w-2xl">
        @if ($showEditTitleModal)
            <form wire:submit="updateContentTitle" class="space-y-4">
                <div>
                    <flux:heading size="lg">{{ __('Edit Content Title') }}</flux:heading>
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

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <flux:select wire:model="editVisualStyle" :label="__('Visual Style')">
                            <flux:select.option value="Dark Fantasy Manhwa">Dark Fantasy Manhwa</flux:select.option>
                            <flux:select.option value="Modern Anime Aesthetic">Modern Anime Aesthetic</flux:select.option>
                            <flux:select.option value="Photorealistic Cinematic">Photorealistic Cinematic</flux:select.option>
                            <flux:select.option value="Cyberpunk Neon">Cyberpunk Neon Webtoon</flux:select.option>
                            <flux:select.option value="Classic Comic Book">Classic Graphic Novel</flux:select.option>
                        </flux:select>
                    </div>

                    <div>
                        <flux:select wire:model="editVoiceName" :label="__('Default Narrator')">
                            @foreach ($voices as $voiceKey => $voiceLabel)
                                <flux:select.option value="{{ $voiceKey }}">{{ $voiceLabel }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>
                </div>

                <!-- Recurring Characters Selection -->
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                        {{ __('Recurring Characters') }}
                    </label>
                    @if ($characters->isEmpty())
                        <p class="text-xs text-zinc-400">{{ __('No characters in library yet.') }}</p>
                    @else
                        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 max-h-40 overflow-y-auto p-1">
                            @foreach ($characters as $character)
                                <label class="flex cursor-pointer items-center gap-2 rounded-lg border p-2 text-xs transition-all hover:bg-zinc-50 dark:hover:bg-zinc-800/50 {{ in_array($character->id, $editSelectedCharacterIds) ? 'border-blue-500 bg-blue-50/30 dark:border-blue-500 dark:bg-blue-950/20' : 'border-zinc-200 dark:border-zinc-700' }}">
                                    <input type="checkbox" wire:model.live="editSelectedCharacterIds" value="{{ $character->id }}" class="rounded text-blue-600">
                                    <span class="font-medium text-zinc-900 dark:text-zinc-100 truncate">{{ $character->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-zinc-100 dark:border-zinc-800">
                    <flux:button type="button" variant="ghost" wire:click="closeEditContentTitleModal">
                        {{ __('Cancel') }}
                    </flux:button>
                    <flux:button type="submit" variant="primary">
                        {{ __('Save Changes') }}
                    </flux:button>
                </div>
            </form>
        @endif
    </flux:modal>
</div>
