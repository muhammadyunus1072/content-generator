<div class="w-full space-y-6">
    <!-- Header with Tabs -->
    <div class="relative overflow-hidden rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="absolute -right-16 -top-16 h-36 w-36 rounded-full bg-violet-500/10 blur-3xl dark:bg-violet-400/15"></div>
        <div class="absolute -bottom-16 -left-16 h-36 w-36 rounded-full bg-fuchsia-500/10 blur-3xl dark:bg-fuchsia-400/15"></div>

        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <flux:heading size="xl" level="1" class="mb-1 bg-gradient-to-r from-violet-600 via-fuchsia-600 to-indigo-600 bg-clip-text text-transparent font-extrabold dark:from-violet-400 dark:via-fuchsia-400 dark:to-indigo-400">
                    {{ __('Comic Studio') }}
                </flux:heading>
                <flux:subheading>{{ __('Motion Comic & Anime Pipeline: Layered audio, camera shake, parallax, speech bubbles & character bibles.') }}</flux:subheading>
            </div>

            <!-- Tabs Nav -->
            <div class="flex items-center gap-3">
                <button
                    wire:click="openImportModal"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-violet-300 bg-violet-50 px-3.5 py-2 text-xs font-semibold text-violet-800 shadow-sm transition hover:bg-violet-100 dark:border-violet-700 dark:bg-violet-950/40 dark:text-violet-300"
                >
                    <flux:icon name="arrow-down-tray" class="h-4 w-4" />
                    <span>{{ __('Import Story / EPUB') }}</span>
                </button>

                <!-- Tabs Nav -->
                <div class="flex items-center rounded-xl border border-zinc-200 bg-zinc-100/70 p-1 dark:border-zinc-800 dark:bg-zinc-800/60">
                    <button
                        wire:click="setTab('titles')"
                        class="flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold transition-all {{ $tab === 'titles' ? 'bg-white text-zinc-900 shadow-sm dark:bg-zinc-900 dark:text-white' : 'text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white' }}"
                    >
                        <flux:icon name="film" class="h-4 w-4 text-violet-500" />
                        {{ __('Comic Titles') }}
                    </button>
                    <button
                        wire:click="setTab('characters')"
                        class="flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold transition-all {{ $tab === 'characters' ? 'bg-white text-zinc-900 shadow-sm dark:bg-zinc-900 dark:text-white' : 'text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white' }}"
                    >
                        <flux:icon name="user-group" class="h-4 w-4 text-fuchsia-500" />
                        {{ __('Character Bible') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB 1: COMIC TITLES & EPISODES -->
    @if ($tab === 'titles')
        <div class="space-y-6">
            <!-- Create New Motion Comic Title Card -->
            <div x-data="{ openCreate: {{ $openCreateForm ? 'true' : 'false' }} }" class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-center justify-between cursor-pointer" x-on:click="openCreate = !openCreate">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-violet-50 text-violet-600 dark:bg-violet-900/30 dark:text-violet-400">
                            <flux:icon name="plus" class="h-5 w-5" />
                        </div>
                        <div>
                            <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Create New Motion Comic Title') }}</h2>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Set up a serialized webtoon/anime series with recurring character bibles, visual styles, and animation presets.') }}</p>
                        </div>
                    </div>
                    <button type="button" class="rounded-lg p-2 text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800">
                        <flux:icon name="chevron-down" class="h-5 w-5 transition-transform duration-200" :class="openCreate ? 'rotate-180' : ''" />
                    </button>
                </div>

                <form wire:submit="createContentTitle" x-show="openCreate" x-transition class="mt-6 space-y-4 border-t border-zinc-100 pt-6 dark:border-zinc-800">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <flux:input wire:model="title" label="{{ __('Series Title') }}" placeholder="{{ __('e.g., Solo Necromancer Awakening') }}" required />
                        </div>
                        <div>
                            <flux:input wire:model="genre" label="{{ __('Genre') }}" placeholder="{{ __('e.g., Action Fantasy, Romance Manhwa') }}" required />
                        </div>
                    </div>

                    <div>
                        <flux:textarea wire:model="description" label="{{ __('Logline / Premise') }}" placeholder="{{ __('Brief summary of the comic story arc...') }}" rows="2" />
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">
                        <div>
                            <flux:input wire:model="visualStyle" label="{{ __('Art Direction Style') }}" placeholder="{{ __('Dark Fantasy Manhwa, Solo Leveling ink...') }}" required />
                        </div>
                        <div>
                            <flux:select wire:model="aspectRatio" label="{{ __('Format / Resolution') }}">
                                <flux:select.option value="16:9">{{ __('16:9 Widescreen (YouTube Long-form)') }}</flux:select.option>
                                <flux:select.option value="9:16">{{ __('9:16 Vertical (TikTok / Shorts)') }}</flux:select.option>
                            </flux:select>
                        </div>
                        <div>
                            <flux:select wire:model="voiceMode" label="{{ __('Voice Acting Mode') }}">
                                <flux:select.option value="multi_actor">{{ __('🎙️ Multi-Actor Drama') }}</flux:select.option>
                                <flux:select.option value="solo_narrator">{{ __('📖 Solo 3rd Person Narrator') }}</flux:select.option>
                            </flux:select>
                        </div>
                        <div>
                            <flux:select wire:model="voiceName" label="{{ __('Narrator / Default Voice') }}">
                                @foreach ($voices as $voiceKey => $voiceLabel)
                                    <flux:select.option value="{{ $voiceKey }}">{{ $voiceLabel }}</flux:select.option>
                                @endforeach
                            </flux:select>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <flux:button type="button" variant="subtle" x-on:click="openCreate = false">{{ __('Cancel') }}</flux:button>
                        <flux:button type="submit" variant="primary" class="bg-violet-600 hover:bg-violet-500">
                            {{ __('Initialize Comic Series') }}
                        </flux:button>
                    </div>
                </form>
            </div>

            <!-- List of Existing Comic Titles -->
            <div class="space-y-4">
                @forelse ($contentTitles as $titleItem)
                    <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <h3 class="text-lg font-bold text-zinc-900 dark:text-zinc-100">{{ $titleItem->title }}</h3>
                                    <span class="rounded-full bg-violet-100 px-2.5 py-0.5 text-xs font-semibold text-violet-700 dark:bg-violet-950/60 dark:text-violet-300">
                                        {{ $titleItem->genre }}
                                    </span>
                                    <span class="rounded-full bg-zinc-100 px-2.5 py-0.5 text-xs font-medium text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400">
                                        {{ $titleItem->aspect_ratio }}
                                    </span>
                                </div>
                                <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ $titleItem->description ?: __('No description provided.') }}</p>
                                <div class="flex flex-wrap items-center gap-4 pt-2 text-xs text-zinc-500">
                                    <span><strong>{{ __('Style') }}:</strong> {{ Str::limit($titleItem->visual_style, 40) }}</span>
                                    <span><strong>{{ __('Stories') }}:</strong> {{ $titleItem->stories->count() }}</span>
                                    <span><strong>{{ __('Characters') }}:</strong> {{ $titleItem->characters->count() }}</span>
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                <a
                                    href="{{ route('story-studio.title', $titleItem->id) }}"
                                    class="inline-flex items-center gap-1.5 rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-2 text-xs font-semibold text-zinc-800 transition hover:bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200"
                                >
                                    <flux:icon name="pencil-square" class="h-4 w-4" />
                                    <span>{{ __('Manage Episodes') }}</span>
                                </a>
                            </div>
                        </div>

                        <!-- Stories & Episodes List within this Title -->
                        @if ($titleItem->stories->isNotEmpty())
                            <div class="mt-6 border-t border-zinc-100 pt-4 dark:border-zinc-800 space-y-4">
                                <h4 class="text-xs font-bold uppercase tracking-wider text-zinc-400">{{ __('Episodes & Motion Comic Scenes') }}</h4>
                                
                                @foreach ($titleItem->stories as $story)
                                    @foreach ($story->episodes as $episode)
                                        <div class="flex flex-col gap-3 rounded-lg border border-zinc-200/80 bg-zinc-50/60 p-4 dark:border-zinc-800/80 dark:bg-zinc-950/40 sm:flex-row sm:items-center sm:justify-between">
                                            <div>
                                                <div class="flex items-center gap-2">
                                                    <span class="font-bold text-sm text-zinc-800 dark:text-zinc-200">EP {{ $episode->episode_number }}: {{ $episode->title }}</span>
                                                    <span class="rounded bg-zinc-200/80 px-2 py-0.5 text-[10px] font-semibold uppercase text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                                                        {{ $episode->status }}
                                                    </span>
                                                    @if ($episode->webtoon_status === 'completed')
                                                        <span class="rounded bg-cyan-100 px-2 py-0.5 text-[10px] font-bold uppercase text-cyan-800 dark:bg-cyan-950 dark:text-cyan-300">
                                                            {{ __('Webtoon Ready') }}
                                                        </span>
                                                    @endif
                                                    @if ($episode->manga_status === 'completed')
                                                        <span class="rounded bg-rose-100 px-2 py-0.5 text-[10px] font-bold uppercase text-rose-800 dark:bg-rose-950 dark:text-rose-300">
                                                            {{ __('Manga Ready') }} ({{ strtoupper($episode->manga_reading_direction ?: 'rtl') }})
                                                        </span>
                                                    @endif
                                                </div>
                                                <p class="text-xs text-zinc-500 mt-0.5">{{ $episode->boards->count() }} {{ __('comic panels') }} | {{ number_format($episode->duration_seconds, 1) }}s</p>
                                            </div>

                                            <div class="flex flex-wrap items-center gap-2">
                                                <!-- Panel Bubbles & SFX Quick Access -->
                                                @if ($episode->boards->isNotEmpty())
                                                    <button
                                                        type="button"
                                                        wire:click="openBubbleEditor({{ $episode->boards->first()->id }})"
                                                        class="inline-flex items-center gap-1 rounded-lg border border-violet-300 bg-violet-50 px-3 py-1.5 text-xs font-semibold text-violet-700 hover:bg-violet-100 dark:border-violet-800 dark:bg-violet-950/40 dark:text-violet-300"
                                                    >
                                                        <flux:icon name="chat-bubble-bottom-center-text" class="h-3.5 w-3.5" />
                                                        <span>{{ __('Bubbles & SFX') }}</span>
                                                    </button>
                                                @endif

                                                <!-- Render Motion Comic Button -->
                                                <button
                                                    type="button"
                                                    wire:click="renderMotionComic({{ $episode->id }})"
                                                    class="inline-flex items-center gap-1 rounded-lg bg-violet-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-violet-500"
                                                >
                                                    <flux:icon name="film" class="h-3.5 w-3.5" />
                                                    <span>{{ __('Motion Comic') }}</span>
                                                </button>

                                                <!-- Generate Webtoon Strip Button -->
                                                <button
                                                    type="button"
                                                    wire:click="generateWebtoonStrip({{ $episode->id }})"
                                                    class="inline-flex items-center gap-1 rounded-lg border border-indigo-300 bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-100 dark:border-indigo-800 dark:bg-indigo-950/40 dark:text-indigo-300"
                                                >
                                                    <flux:icon name="squares-2x2" class="h-3.5 w-3.5" />
                                                    <span>{{ __('Build Strip') }}</span>
                                                </button>

                                                <!-- Voice Mode Toggle (Multi-Actor vs Solo Narrator) -->
                                                <button
                                                    type="button"
                                                    wire:click="toggleEpisodeVoiceMode({{ $episode->id }})"
                                                    class="inline-flex items-center gap-1 rounded-lg border px-3 py-1.5 text-xs font-semibold transition {{ $episode->getEffectiveVoiceMode() === 'multi_actor' ? 'border-violet-400 bg-violet-50 text-violet-800 hover:bg-violet-100 dark:border-violet-700 dark:bg-violet-950/40 dark:text-violet-300' : 'border-indigo-300 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 dark:border-indigo-800 dark:bg-indigo-950/40 dark:text-indigo-300' }}"
                                                    title="{{ __('Click to switch between Multi-Actor Voice Drama and Solo 3rd Person Narrator') }}"
                                                >
                                                    @if ($episode->getEffectiveVoiceMode() === 'multi_actor')
                                                        <flux:icon name="user-group" class="h-3.5 w-3.5 text-violet-600" />
                                                        <span>{{ __('Multi-Actor') }}</span>
                                                    @else
                                                        <flux:icon name="microphone" class="h-3.5 w-3.5 text-indigo-600" />
                                                        <span>{{ __('Solo Narrator') }}</span>
                                                    @endif
                                                </button>

                                                <!-- Synthesize All Episode Voices Button -->
                                                <button
                                                    type="button"
                                                    wire:click="synthesizeEpisodeVoices({{ $episode->id }})"
                                                    class="inline-flex items-center gap-1 rounded-lg border border-violet-300 bg-violet-50 px-3 py-1.5 text-xs font-semibold text-violet-700 hover:bg-violet-100 dark:border-violet-800 dark:bg-violet-950/40 dark:text-violet-300"
                                                    title="{{ __('Synthesize multi-actor voice acting for all panels') }}"
                                                >
                                                    <flux:icon name="speaker-wave" class="h-3.5 w-3.5" />
                                                    <span>{{ __('Voice Drama') }}</span>
                                                </button>

                                                <!-- Webtoon Reader Link -->
                                                @if ($episode->webtoon_slices && count($episode->webtoon_slices) > 0)
                                                    <a
                                                        href="{{ route('webtoon-reader', $episode->id) }}"
                                                        target="_blank"
                                                        class="inline-flex items-center gap-1 rounded-lg border border-cyan-400 bg-cyan-50 px-3 py-1.5 text-xs font-semibold text-cyan-800 hover:bg-cyan-100 dark:bg-cyan-950/40 dark:text-cyan-300"
                                                    >
                                                        <flux:icon name="book-open" class="h-3.5 w-3.5" />
                                                        <span>{{ __('Read Webtoon') }}</span>
                                                    </a>

                                                    <!-- Export Publishing Pack Button -->
                                                    <button
                                                        type="button"
                                                        wire:click="exportWebtoonPack({{ $episode->id }})"
                                                        class="inline-flex items-center gap-1 rounded-lg border border-fuchsia-300 bg-fuchsia-50 px-3 py-1.5 text-xs font-semibold text-fuchsia-800 hover:bg-fuchsia-100 dark:border-fuchsia-800 dark:bg-fuchsia-950/40 dark:text-fuchsia-300"
                                                    >
                                                        <flux:icon name="arrow-down-tray" class="h-3.5 w-3.5" />
                                                        <span>{{ __('Export Pack') }}</span>
                                                    </button>
                                                @endif

                                                <!-- Download Publishing Pack ZIP -->
                                                @if ($episode->webtoon_export_pack_path)
                                                    <a
                                                        href="{{ $episode->webtoon_export_pack_url }}"
                                                        download
                                                        class="inline-flex items-center gap-1 rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-emerald-500"
                                                    >
                                                        <flux:icon name="arrow-down-tray" class="h-3.5 w-3.5" />
                                                        <span>{{ __('Download ZIP') }}</span>
                                                    </a>
                                                @endif

                                                <!-- Generate Manga Pages Button -->
                                                <button
                                                    type="button"
                                                    wire:click="generateMangaPages({{ $episode->id }}, 'rtl')"
                                                    class="inline-flex items-center gap-1 rounded-lg border border-rose-300 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-100 dark:border-rose-800 dark:bg-rose-950/40 dark:text-rose-300"
                                                >
                                                    <flux:icon name="book-open" class="h-3.5 w-3.5" />
                                                    <span>{{ __('Build Manga') }}</span>
                                                </button>

                                                <!-- Manga Reader Link -->
                                                @if ($episode->manga_pages && count($episode->manga_pages) > 0)
                                                    <a
                                                        href="{{ route('manga-reader', $episode->id) }}"
                                                        target="_blank"
                                                        class="inline-flex items-center gap-1 rounded-lg border border-rose-400 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-800 hover:bg-rose-100 dark:bg-rose-950/40 dark:text-rose-300"
                                                    >
                                                        <flux:icon name="book-open" class="h-3.5 w-3.5" />
                                                        <span>{{ __('Read Spread') }} ({{ count($episode->manga_pages) }}p)</span>
                                                    </a>

                                                    <!-- Export CBZ / PDF Archive -->
                                                    <button
                                                        type="button"
                                                        wire:click="exportComicArchive({{ $episode->id }})"
                                                        class="inline-flex items-center gap-1 rounded-lg border border-amber-300 bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-800 hover:bg-amber-100 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-300"
                                                    >
                                                        <flux:icon name="archive-box" class="h-3.5 w-3.5" />
                                                        <span>{{ __('Export CBZ/PDF') }}</span>
                                                    </button>
                                                @endif

                                                @if ($episode->cbz_package_path)
                                                    <a
                                                        href="{{ $episode->cbz_package_url }}"
                                                        download
                                                        class="inline-flex items-center gap-1 rounded-lg bg-amber-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-amber-500"
                                                    >
                                                        <flux:icon name="arrow-down-tray" class="h-3.5 w-3.5" />
                                                        <span>{{ __('CBZ') }}</span>
                                                    </a>
                                                @endif

                                                @if ($episode->pdf_package_path)
                                                    <a
                                                        href="{{ $episode->pdf_package_url }}"
                                                        download
                                                        class="inline-flex items-center gap-1 rounded-lg bg-rose-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-rose-500"
                                                    >
                                                        <flux:icon name="document-arrow-down" class="h-3.5 w-3.5" />
                                                        <span>{{ __('PDF') }}</span>
                                                    </a>
                                                @endif

                                                @if ($episode->video_path)
                                                    <a
                                                        href="{{ $episode->video_url }}"
                                                        target="_blank"
                                                        class="inline-flex items-center gap-1 rounded-lg border border-emerald-400 bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-800 hover:bg-emerald-100 dark:bg-emerald-950/40 dark:text-emerald-300"
                                                    >
                                                        <flux:icon name="play" class="h-3.5 w-3.5" />
                                                        <span>{{ __('Watch Video') }}</span>
                                                    </a>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                @endforeach
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="rounded-xl border border-dashed border-zinc-300 p-12 text-center dark:border-zinc-700">
                        <flux:icon name="film" class="mx-auto h-12 w-12 text-zinc-400" />
                        <h3 class="mt-2 text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ __('No Motion Comic Titles yet') }}</h3>
                        <p class="mt-1 text-sm text-zinc-500">{{ __('Create your first serialized motion comic or launch one directly from Content Factory.') }}</p>
                    </div>
                @endforelse
            </div>
        </div>
    @endif

    <!-- TAB 2: CHARACTER BIBLE -->
    @if ($tab === 'characters')
        <div class="space-y-6">
            <!-- Add / Edit Character Bible Form -->
            <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-center justify-between pb-4 border-b border-zinc-100 dark:border-zinc-800">
                    <div>
                        <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">
                            {{ $editingCharacterId ? __('Edit Character Bible Sheet') : __('Register New Character in Bible') }}
                        </h2>
                        <p class="text-xs text-zinc-500">{{ __('Define personality, appearance, voice actor mapping, and visual tags for cross-episode consistency.') }}</p>
                    </div>

                    @if ($editingCharacterId)
                        <flux:button wire:click="resetCharacterForm" size="xs" variant="subtle">{{ __('New Character') }}</flux:button>
                    @endif
                </div>

                <form wire:submit="saveCharacter" class="mt-4 space-y-4">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">
                        <div class="sm:col-span-2">
                            <flux:input wire:model="characterName" label="{{ __('Character Name') }}" placeholder="{{ __('e.g., Aoyama Ren') }}" required />
                        </div>
                        <div>
                            <flux:select wire:model="characterGender" label="{{ __('Gender') }}">
                                <flux:select.option value="male">{{ __('Male') }}</flux:select.option>
                                <flux:select.option value="female">{{ __('Female') }}</flux:select.option>
                                <flux:select.option value="other">{{ __('Other / Creature') }}</flux:select.option>
                            </flux:select>
                        </div>
                        <div>
                            <flux:input wire:model="characterAge" type="number" label="{{ __('Age') }}" placeholder="24" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <flux:select wire:model="characterRole" label="{{ __('Story Role') }}">
                                <flux:select.option value="protagonist">{{ __('Protagonist (MC)') }}</flux:select.option>
                                <flux:select.option value="antagonist">{{ __('Antagonist / Rival') }}</flux:select.option>
                                <flux:select.option value="supporting">{{ __('Supporting Cast') }}</flux:select.option>
                                <flux:select.option value="mentor">{{ __('Mentor / Guide') }}</flux:select.option>
                                <flux:select.option value="narrator">{{ __('Narrator') }}</flux:select.option>
                            </flux:select>
                        </div>
                        <div>
                            <flux:select wire:model="characterVoiceId" label="{{ __('Voice Actor (TTS)') }}">
                                @foreach ($voices as $voiceKey => $voiceLabel)
                                    <flux:select.option value="{{ $voiceKey }}">{{ $voiceLabel }}</flux:select.option>
                                @endforeach
                            </flux:select>
                        </div>
                        <div>
                            <flux:input wire:model="characterColorTheme" label="{{ __('Signature Color / Theme') }}" placeholder="{{ __('e.g. #3B82F6 or Neon Cyan') }}" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <flux:textarea wire:model="characterAppearance" label="{{ __('Visual Appearance (Costume, Hair, Eyes)') }}" rows="3" placeholder="{{ __('Jet-black messy hair, glowing electric-blue eyes, long dark coat with silver trims...') }}" />
                        </div>
                        <div>
                            <flux:textarea wire:model="characterPersonality" label="{{ __('Personality & Speech Demeanor') }}" rows="3" placeholder="{{ __('Calm, analytical, speaks with short decisive sentences. Reluctant hero.') }}" />
                        </div>
                    </div>

                    <div>
                        <flux:input wire:model="characterPromptKeywords" label="{{ __('Image Generation Consistency Tokens (Flux / SDXL)') }}" placeholder="{{ __('Aoyama Ren, solo, male, glowing blue eyes, dark trench coat, sharp jawline, manhwa style') }}" required />
                    </div>

                    <div class="flex items-center justify-between pt-2">
                        <button
                            type="button"
                            wire:click="generateAiCharacterProfile"
                            class="inline-flex items-center gap-1.5 rounded-xl border border-fuchsia-300 bg-fuchsia-50 px-3.5 py-2 text-xs font-semibold text-fuchsia-800 transition hover:bg-fuchsia-100 dark:border-fuchsia-800 dark:bg-fuchsia-950/40 dark:text-fuchsia-300"
                        >
                            <flux:icon name="sparkles" class="h-4 w-4" />
                            <span>{{ __('AI Auto-Generate Profile & Prompts') }}</span>
                        </button>

                        <flux:button type="submit" variant="primary" class="bg-violet-600 hover:bg-violet-500">
                            {{ $editingCharacterId ? __('Update Character') : __('Save Character to Bible') }}
                        </flux:button>
                    </div>
                </form>
            </div>

            <!-- Character Cards Grid -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @forelse ($characters as $char)
                    <div class="relative overflow-hidden rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                        <div class="flex items-start justify-between">
                            <div>
                                <div class="flex items-center gap-2">
                                    <h4 class="font-bold text-zinc-900 dark:text-zinc-100">{{ $char->name }}</h4>
                                    <span class="rounded px-2 py-0.5 text-[10px] font-bold uppercase {{ $char->role === 'protagonist' ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300' : ($char->role === 'antagonist' ? 'bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300' : 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300') }}">
                                        {{ $char->role ?? 'supporting' }}
                                    </span>
                                </div>
                                <p class="text-xs text-zinc-500">{{ ucfirst($char->gender) }}, {{ $char->age }} y/o</p>
                            </div>

                            <div class="flex items-center gap-1">
                                <button type="button" wire:click="editCharacter({{ $char->id }})" class="rounded p-1 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200">
                                    <flux:icon name="pencil" class="h-4 w-4" />
                                </button>
                                <button type="button" wire:click="deleteCharacter({{ $char->id }})" class="rounded p-1 text-zinc-400 hover:text-rose-600">
                                    <flux:icon name="trash" class="h-4 w-4" />
                                </button>
                            </div>
                        </div>

                        <p class="mt-3 text-xs text-zinc-600 dark:text-zinc-400 line-clamp-2">{{ $char->appearance ?: __('No appearance notes.') }}</p>

                        <div class="mt-3 border-t border-zinc-100 pt-3 text-[11px] text-zinc-500 dark:border-zinc-800 space-y-1">
                            <div><strong>{{ __('Voice') }}:</strong> {{ $char->voice_id ?? 'Default' }}</div>
                            <div><strong>{{ __('Tokens') }}:</strong> <span class="font-mono text-[10px] text-violet-600 dark:text-violet-400">{{ Str::limit($char->prompt_keywords, 50) }}</span></div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full rounded-xl border border-dashed border-zinc-300 p-8 text-center text-zinc-500">
                        {{ __('No characters registered yet. Use the form above to add characters.') }}
                    </div>
                @endforelse
            </div>
        </div>
    @endif

    <!-- PANEL BUBBLE, SFX & CAMERA KEYFRAME DIRECTOR MODAL -->
    @if ($showBubbleModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/75 p-4 backdrop-blur-md"
             x-data="{
                 activeTab: 'bubbles',
                 selectedType: 'bubble',
                 selectedIdx: 0,
                 canvasClick(e) {
                     const rect = e.currentTarget.getBoundingClientRect();
                     const x = Math.round(Math.max(5, Math.min(95, ((e.clientX - rect.left) / rect.width) * 100)));
                     const y = Math.round(Math.max(5, Math.min(95, ((e.clientY - rect.top) / rect.height) * 100)));
                     if (this.selectedType === 'bubble') {
                         $wire.updateBubbleCoords(this.selectedIdx, x, y);
                     } else if (this.selectedType === 'sfx') {
                         $wire.updateSfxCoords(this.selectedIdx, x, y);
                     } else if (this.selectedType === 'keyframe') {
                         $wire.set('kfXPct', x);
                         $wire.set('kfYPct', y);
                     }
                 }
             }"
             x-on:keydown.escape.window="$wire.set('showBubbleModal', false)">
            <div class="relative max-h-[92vh] w-full max-w-6xl overflow-y-auto rounded-2xl border border-zinc-200 bg-white p-6 shadow-2xl dark:border-zinc-800 dark:bg-zinc-900">
                <!-- Close Button -->
                <button
                    type="button"
                    wire:click="$set('showBubbleModal', false)"
                    class="absolute right-4 top-4 rounded-xl p-2 text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800"
                >
                    <flux:icon name="x-mark" class="h-5 w-5" />
                </button>

                <!-- Header -->
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-violet-600/10 text-violet-600 dark:bg-violet-500/20 dark:text-violet-300">
                        <flux:icon name="film" class="h-6 w-6" />
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-lg font-bold text-zinc-900 dark:text-zinc-100">
                                {{ __('Visual Panel Director & Timeline Canvas') }}
                            </h3>
                            @php
                                $currentBoard = $editingBoardId ? \App\Models\SceneBoard::find($editingBoardId) : null;
                                $effectiveMode = $currentBoard?->episode?->getEffectiveVoiceMode() ?? 'multi_actor';
                            @endphp
                            <span class="rounded px-2 py-0.5 text-[10px] font-bold uppercase {{ $effectiveMode === 'multi_actor' ? 'bg-violet-100 text-violet-700 dark:bg-violet-950 dark:text-violet-300' : 'bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300' }}">
                                {{ $effectiveMode === 'multi_actor' ? __('🎙️ Multi-Actor Drama') : __('📖 Solo Narrator') }}
                            </span>
                        </div>
                        <p class="text-xs text-zinc-500">{{ __('Drag-and-drop bubble coordinates, Japanese SFX lettering, dynamic camera keyframes, and layout physics.') }}</p>
                    </div>
                </div>

                <!-- 2-Column Grid Layout -->
                <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-12">
                    <!-- Left: Interactive 2D Canvas & Timeline (7 Cols) -->
                    <div class="lg:col-span-7 flex flex-col gap-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold uppercase tracking-wider text-zinc-400">{{ __('Interactive 2D Panel Canvas') }}</span>
                            <div class="flex items-center gap-2 text-xs">
                                <span class="text-zinc-500">{{ __('Target:') }}</span>
                                <button type="button" @click="selectedType = 'bubble'" :class="selectedType === 'bubble' ? 'bg-violet-600 text-white font-bold' : 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300'" class="rounded px-2 py-0.5 text-[11px] transition">
                                    {{ __('Bubble') }}
                                </button>
                                <button type="button" @click="selectedType = 'sfx'" :class="selectedType === 'sfx' ? 'bg-rose-600 text-white font-bold' : 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300'" class="rounded px-2 py-0.5 text-[11px] transition">
                                    {{ __('SFX') }}
                                </button>
                                <button type="button" @click="selectedType = 'keyframe'" :class="selectedType === 'keyframe' ? 'bg-amber-600 text-white font-bold' : 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300'" class="rounded px-2 py-0.5 text-[11px] transition">
                                    {{ __('Camera Target') }}
                                </button>
                            </div>
                        </div>

                        <!-- Canvas Box -->
                        <div class="relative w-full aspect-video rounded-xl overflow-hidden border border-zinc-700 bg-zinc-950 select-none shadow-2xl cursor-crosshair group"
                             @click="canvasClick($event)">
                            @if ($editingBoardImageUrl)
                                <img src="{{ $editingBoardImageUrl }}" class="w-full h-full object-cover pointer-events-none opacity-85" alt="Panel Artwork" />
                            @else
                                <div class="w-full h-full flex flex-col items-center justify-center text-zinc-600">
                                    <flux:icon name="photo" class="h-12 w-12 stroke-[1.2]" />
                                    <span class="mt-2 text-xs">{{ __('No panel artwork image rendered yet') }}</span>
                                </div>
                            @endif

                            <!-- Speech Bubble Overlays -->
                            @foreach ($boardBubbles as $bIdx => $b)
                                @php
                                    $bx = $b['x_pct'] ?? (str_contains($b['position'] ?? '', 'left') ? 25 : 75);
                                    $by = $b['y_pct'] ?? (str_contains($b['position'] ?? '', 'top') ? 25 : 75);
                                @endphp
                                <div class="absolute cursor-pointer transition-transform hover:scale-105"
                                     style="left: {{ $bx }}%; top: {{ $by }}%; transform: translate(-50%, -50%);"
                                     @click.stop="selectedType = 'bubble'; selectedIdx = {{ $bIdx }};">
                                    <div class="relative max-w-[160px] rounded-2xl border-2 px-3 py-1.5 shadow-lg backdrop-blur-xs text-center"
                                         :class="selectedType === 'bubble' && selectedIdx === {{ $bIdx }} ? 'border-violet-500 bg-white text-zinc-900 ring-4 ring-violet-400/40' : 'border-zinc-900 bg-white/95 text-zinc-900'">
                                        @if (! empty($b['speaker']))
                                            <div class="text-[9px] font-black uppercase tracking-wider text-violet-700 leading-tight">{{ $b['speaker'] }}</div>
                                        @endif
                                        <div class="text-[11px] font-bold leading-tight line-clamp-2">{{ $b['text'] }}</div>
                                        <!-- Bubble Tail Indicator -->
                                        <div class="absolute -bottom-1.5 left-1/2 -translate-x-1/2 w-2.5 h-2.5 rotate-45 border-b-2 border-r-2 border-zinc-900 bg-white"></div>
                                    </div>
                                    <div class="mt-1 text-center">
                                        <span class="rounded bg-black/75 px-1.5 py-0.5 text-[9px] font-mono text-white">#{{ $bIdx + 1 }} ({{ $bx }}%, {{ $by }}%)</span>
                                    </div>
                                </div>
                            @endforeach

                            <!-- SFX Typography Overlays -->
                            @foreach ($boardSfxTypography as $sIdx => $s)
                                @php
                                    $sx = $s['x_pct'] ?? 50;
                                    $sy = $s['y_pct'] ?? 50;
                                    $sRot = $s['rotation'] ?? -12;
                                @endphp
                                <div class="absolute cursor-pointer transition-transform hover:scale-110"
                                     style="left: {{ $sx }}%; top: {{ $sy }}%; transform: translate(-50%, -50%) rotate({{ $sRot }}deg);"
                                     @click.stop="selectedType = 'sfx'; selectedIdx = {{ $sIdx }};">
                                    <div class="px-2 py-0.5 text-center font-black uppercase tracking-widest drop-shadow-[0_4px_6px_rgba(0,0,0,0.8)]"
                                         :class="selectedType === 'sfx' && selectedIdx === {{ $sIdx }} ? 'ring-2 ring-rose-400 rounded' : ''"
                                         style="color: {{ $s['color'] ?? '#EF4444' }}; font-size: 24px; text-shadow: 2px 2px 0 #000, -2px -2px 0 #000, 2px -2px 0 #000, -2px 2px 0 #000;">
                                        {{ $s['text'] }}
                                    </div>
                                    <div class="text-center">
                                        <span class="rounded bg-black/80 px-1 py-0.5 text-[9px] font-mono text-rose-300">SFX #{{ $sIdx + 1 }}</span>
                                    </div>
                                </div>
                            @endforeach

                            <!-- Camera Focus Indicator (When keyframe selected) -->
                            <div class="absolute pointer-events-none transition-all duration-300 border-2 border-amber-400/80 bg-amber-400/10 rounded"
                                 style="left: {{ $kfXPct }}%; top: {{ $kfYPct }}%; width: {{ 100 / max(1.0, (float)$kfZoom) }}%; height: {{ 100 / max(1.0, (float)$kfZoom) }}%; transform: translate(-50%, -50%);"
                                 x-show="selectedType === 'keyframe'">
                                <div class="absolute -top-5 left-0 rounded bg-amber-500 px-1.5 py-0.5 text-[9px] font-bold text-black uppercase">
                                    Camera Zoom: {{ $kfZoom }}x ({{ $kfXPct }}%, {{ $kfYPct }}%)
                                </div>
                            </div>
                        </div>

                        <!-- Canvas Placement Helper Tip -->
                        <div class="flex items-center justify-between rounded-lg bg-zinc-100 p-2 text-[11px] text-zinc-600 dark:bg-zinc-800/80 dark:text-zinc-400">
                            <span class="flex items-center gap-1.5">
                                <flux:icon name="cursor-arrow-rays" class="h-4 w-4 text-violet-500" />
                                <span>{{ __('Click anywhere on panel to place active item.') }}</span>
                            </span>
                            <span class="font-mono text-[10px] text-zinc-500" x-text="'Active Target: ' + selectedType.toUpperCase() + ' #' + (selectedIdx + 1)"></span>
                        </div>

                        <!-- Audio Drama Player (When Synthesized) -->
                        @if ($editingBoardAudioUrl)
                            <div class="rounded-xl border border-violet-300/60 bg-violet-50/50 p-2.5 dark:border-violet-900/40 dark:bg-violet-950/20 flex flex-col gap-1.5 shadow-sm">
                                <div class="flex items-center justify-between text-xs font-bold text-violet-700 dark:text-violet-300">
                                    <span class="flex items-center gap-1.5">
                                        <flux:icon name="speaker-wave" class="h-4 w-4 text-violet-600" />
                                        {{ __('Synthesized Dialogue Track') }}
                                    </span>
                                    @if (! empty($boardDialogueTimeline))
                                        <span class="text-[10px] text-zinc-500 font-mono">{{ count($boardDialogueTimeline) }} {{ __('actors / lines') }}</span>
                                    @endif
                                </div>
                                <audio controls src="{{ $editingBoardAudioUrl }}" class="w-full h-8 rounded accent-violet-600"></audio>
                            </div>
                        @endif

                        <!-- Mini Timeline Scrubber Bar -->
                        <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-3 dark:border-zinc-800 dark:bg-zinc-950">
                            <div class="flex items-center justify-between text-xs font-bold text-zinc-700 dark:text-zinc-300">
                                <span class="flex items-center gap-1">
                                    <flux:icon name="clock" class="h-4 w-4 text-amber-500" />
                                    {{ __('Timeline Camera Keyframes') }} ({{ count($boardKeyframes) }})
                                </span>
                                <span class="text-[10px] text-zinc-400">{{ __('0% (Start) -> 100% (End of Board)') }}</span>
                            </div>

                            <!-- Visual Timeline Bar -->
                            <div class="relative mt-3 h-6 w-full rounded-lg bg-zinc-200 dark:bg-zinc-800">
                                @foreach ($boardKeyframes as $kfIdx => $kf)
                                    <div class="absolute top-0 bottom-0 w-3 -ml-1.5 cursor-pointer rounded-full bg-amber-500 shadow hover:scale-125 transition"
                                         style="left: {{ $kf['time_pct'] ?? 0 }}%;"
                                         title="Keyframe {{ $kfIdx + 1 }}: {{ $kf['time_pct'] }}% (Zoom: {{ $kf['zoom'] }}x)">
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <!-- Right: Tabbed Director Controls (5 Cols) -->
                    <div class="lg:col-span-5 flex flex-col">
                        <!-- Navigation Tabs -->
                        <div class="flex items-center gap-1 rounded-xl border border-zinc-200 bg-zinc-100 p-1 dark:border-zinc-800 dark:bg-zinc-950">
                            <button
                                type="button"
                                @click="activeTab = 'bubbles'"
                                :class="activeTab === 'bubbles' ? 'bg-white shadow text-violet-600 dark:bg-zinc-800 dark:text-violet-400 font-bold' : 'text-zinc-600 hover:text-zinc-900 dark:text-zinc-400'"
                                class="flex-1 rounded-lg py-1.5 text-xs transition text-center"
                            >
                                {{ __('Dialogue & SFX') }}
                            </button>
                            <button
                                type="button"
                                @click="activeTab = 'keyframes'; selectedType = 'keyframe';"
                                :class="activeTab === 'keyframes' ? 'bg-white shadow text-amber-600 dark:bg-zinc-800 dark:text-amber-400 font-bold' : 'text-zinc-600 hover:text-zinc-900 dark:text-zinc-400'"
                                class="flex-1 rounded-lg py-1.5 text-xs transition text-center"
                            >
                                {{ __('Camera Timeline') }}
                            </button>
                            <button
                                type="button"
                                @click="activeTab = 'fx'"
                                :class="activeTab === 'fx' ? 'bg-white shadow text-indigo-600 dark:bg-zinc-800 dark:text-indigo-400 font-bold' : 'text-zinc-600 hover:text-zinc-900 dark:text-zinc-400'"
                                class="flex-1 rounded-lg py-1.5 text-xs transition text-center"
                            >
                                {{ __('Motion & Physics') }}
                            </button>
                        </div>

                        <!-- TAB 1: DIALOGUE BUBBLES & SFX -->
                        <div x-show="activeTab === 'bubbles'" class="mt-4 space-y-4">
                            <!-- Bubbles List -->
                            <div>
                                <h4 class="text-xs font-bold uppercase text-zinc-400">{{ __('Speech Bubbles') }} ({{ count($boardBubbles) }})</h4>
                                <div class="mt-1 max-h-40 overflow-y-auto space-y-1.5 pr-1">
                                    @forelse ($boardBubbles as $idx => $bub)
                                        <div class="flex items-center justify-between rounded-lg border border-zinc-200 bg-zinc-50 p-2 text-xs dark:border-zinc-800 dark:bg-zinc-950 cursor-pointer hover:border-violet-400"
                                             @click="selectedType = 'bubble'; selectedIdx = {{ $idx }};">
                                            <div class="truncate mr-2">
                                                <span class="font-bold text-violet-600 dark:text-violet-400">#{{ $idx + 1 }} [{{ strtoupper($bub['speaker'] ?: 'Narrator') }} <span class="text-[10px] text-violet-400 font-normal">({{ $bub['emotion'] ?? 'neutral' }})</span>]:</span>
                                                <span class="text-zinc-800 dark:text-zinc-200">"{{ $bub['text'] }}"</span>
                                                <span class="ml-1 text-[10px] text-zinc-400">({{ $bub['x_pct'] ?? 50 }}%, {{ $bub['y_pct'] ?? 30 }}%)</span>
                                            </div>
                                            <button type="button" wire:click="removeBubble({{ $idx }})" class="text-rose-500 hover:text-rose-700">
                                                <flux:icon name="trash" class="h-3.5 w-3.5" />
                                            </button>
                                        </div>
                                    @empty
                                        <p class="text-xs text-zinc-500 italic">{{ __('No bubbles attached yet.') }}</p>
                                    @endforelse
                                </div>
                            </div>

                            <!-- Add Bubble Form -->
                            <div class="rounded-xl border border-dashed border-zinc-300 p-3 dark:border-zinc-700 space-y-2.5">
                                <span class="text-xs font-bold text-zinc-700 dark:text-zinc-300">{{ __('Add New Bubble') }}</span>
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                                    <flux:input wire:model="bubbleSpeaker" label="{{ __('Speaker') }}" placeholder="Character name" />
                                    <flux:select wire:model="bubbleStyle" label="{{ __('Style') }}">
                                        <flux:select.option value="manga">{{ __('Manga Outline') }}</flux:select.option>
                                        <flux:select.option value="webtoon">{{ __('Webtoon Indigo') }}</flux:select.option>
                                        <flux:select.option value="caption_box">{{ __('Caption Box') }}</flux:select.option>
                                    </flux:select>
                                    <flux:select wire:model="bubbleEmotion" label="{{ __('Emotion') }}">
                                        <flux:select.option value="neutral">{{ __('Neutral') }}</flux:select.option>
                                        <flux:select.option value="intense">{{ __('Intense (+15% Rate)') }}</flux:select.option>
                                        <flux:select.option value="shouting">{{ __('Shouting (+25% Rate)') }}</flux:select.option>
                                        <flux:select.option value="whisper">{{ __('Whisper (-15% Rate)') }}</flux:select.option>
                                        <flux:select.option value="sorrow">{{ __('Sorrow (-20% Rate)') }}</flux:select.option>
                                        <flux:select.option value="joy">{{ __('Joy (+15% Rate)') }}</flux:select.option>
                                        <flux:select.option value="terrified">{{ __('Terrified (+10% Rate)') }}</flux:select.option>
                                    </flux:select>
                                </div>
                                <flux:input wire:model="bubbleText" label="{{ __('Dialogue Line') }}" placeholder="{{ __('What does the character say?') }}" />
                                <div class="flex items-center justify-between">
                                    <flux:button type="button" wire:click="synthesizeBoardVoice({{ $editingBoardId }})" size="sm" variant="subtle" class="border-violet-300 text-violet-700 hover:bg-violet-50">
                                        <flux:icon name="speaker-wave" class="h-3.5 w-3.5 mr-1" />
                                        {{ __('Synthesize Dialogue Audio') }}
                                    </flux:button>
                                    <flux:button type="button" wire:click="addBubbleToBoard" size="sm" variant="subtle" class="border-violet-300 text-violet-700">
                                        {{ __('+ Add Bubble') }}
                                    </flux:button>
                                </div>
                            </div>

                            <!-- SFX List & Add -->
                            <div class="border-t border-zinc-100 pt-3 dark:border-zinc-800 space-y-2">
                                <h4 class="text-xs font-bold uppercase text-zinc-400">{{ __('SFX Lettering') }} ({{ count($boardSfxTypography) }})</h4>
                                <div class="max-h-32 overflow-y-auto space-y-1.5 pr-1">
                                    @forelse ($boardSfxTypography as $idx => $sfx)
                                        <div class="flex items-center justify-between rounded-lg border border-red-200 bg-red-50/40 p-2 text-xs dark:border-red-900/40 dark:bg-red-950/20 cursor-pointer hover:border-red-400"
                                             @click="selectedType = 'sfx'; selectedIdx = {{ $idx }};">
                                            <div class="flex items-center gap-1.5">
                                                <span class="h-2.5 w-2.5 rounded-full" style="background-color: {{ $sfx['color'] }}"></span>
                                                <span class="font-extrabold uppercase text-rose-600 dark:text-rose-400 font-serif">{{ $sfx['text'] }}</span>
                                                <span class="text-[10px] text-zinc-400">({{ $sfx['x_pct'] ?? 50 }}%, {{ $sfx['y_pct'] ?? 50 }}%)</span>
                                            </div>
                                            <button type="button" wire:click="removeSfx({{ $idx }})" class="text-rose-500 hover:text-rose-700">
                                                <flux:icon name="trash" class="h-3.5 w-3.5" />
                                            </button>
                                        </div>
                                    @empty
                                        <p class="text-xs text-zinc-500 italic">{{ __('No sound effects added yet.') }}</p>
                                    @endforelse
                                </div>

                                <div class="rounded-xl border border-dashed border-red-200 p-3 dark:border-red-900/40 space-y-2 bg-red-50/20 dark:bg-red-950/10">
                                    <div class="grid grid-cols-2 gap-2">
                                        <flux:input wire:model="sfxText" label="{{ __('SFX Word') }}" placeholder="BOOOM!, SLASH!" />
                                        <flux:select wire:model="sfxStyle" label="{{ __('SFX Style') }}">
                                            <flux:select.option value="action_speed">{{ __('Action Speed') }}</flux:select.option>
                                            <flux:select.option value="blade_slash">{{ __('Blade Slash') }}</flux:select.option>
                                            <flux:select.option value="impact_quake">{{ __('Impact Quake') }}</flux:select.option>
                                            <flux:select.option value="eerie_whisper">{{ __('Eerie Whisper') }}</flux:select.option>
                                        </flux:select>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-1.5">
                                            @foreach (['#EF4444', '#F59E0B', '#8B5CF6', '#10B981', '#FFFFFF'] as $presetColor)
                                                <button
                                                    type="button"
                                                    wire:click="$set('sfxColor', '{{ $presetColor }}')"
                                                    class="h-4 w-4 rounded-full border border-black/30 transition hover:scale-110 {{ $sfxColor === $presetColor ? 'ring-2 ring-violet-500' : '' }}"
                                                    style="background-color: {{ $presetColor }}"
                                                ></button>
                                            @endforeach
                                        </div>
                                        <flux:button type="button" wire:click="addSfxToBoard" size="sm" variant="subtle" class="border-red-300 text-red-700">
                                            {{ __('+ Add SFX') }}
                                        </flux:button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- TAB 2: CAMERA TIMELINE KEYFRAMES -->
                        <div x-show="activeTab === 'keyframes'" class="mt-4 space-y-4">
                            <div>
                                <h4 class="text-xs font-bold uppercase text-amber-500">{{ __('Dynamic Camera Keyframes') }}</h4>
                                <p class="text-[11px] text-zinc-500">{{ __('Add custom camera keyframe stops to compile continuous FFmpeg motion pans.') }}</p>
                            </div>

                            <!-- Add Keyframe Controls -->
                            <div class="rounded-xl border border-amber-300/50 bg-amber-50/30 p-3.5 dark:border-amber-900/40 dark:bg-amber-950/20 space-y-3">
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-1">
                                            {{ __('Time Position:') }} <span class="font-mono text-amber-600">{{ $kfTimePct }}%</span>
                                        </label>
                                        <input type="range" wire:model.live="kfTimePct" min="0" max="100" class="w-full accent-amber-500" />
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-1">
                                            {{ __('Camera Zoom:') }} <span class="font-mono text-amber-600">{{ $kfZoom }}x</span>
                                        </label>
                                        <input type="range" wire:model.live="kfZoom" min="1.0" max="2.5" step="0.05" class="w-full accent-amber-500" />
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-1">
                                            {{ __('Focus Center X:') }} <span class="font-mono text-amber-600">{{ $kfXPct }}%</span>
                                        </label>
                                        <input type="range" wire:model.live="kfXPct" min="0" max="100" class="w-full accent-amber-500" />
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-1">
                                            {{ __('Focus Center Y:') }} <span class="font-mono text-amber-600">{{ $kfYPct }}%</span>
                                        </label>
                                        <input type="range" wire:model.live="kfYPct" min="0" max="100" class="w-full accent-amber-500" />
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-1">
                                        {{ __('Camera Shake (Action Impact):') }} <span class="font-mono text-rose-500">{{ $kfShake > 0 ? $kfShake.'px' : 'Off' }}</span>
                                    </label>
                                    <input type="range" wire:model.live="kfShake" min="0" max="20" step="1" class="w-full accent-rose-500" />
                                </div>

                                <div class="flex justify-end pt-1">
                                    <flux:button type="button" wire:click="addKeyframe" size="sm" variant="primary" class="bg-amber-600 hover:bg-amber-500">
                                        {{ __('+ Add Keyframe Point') }}
                                    </flux:button>
                                </div>
                            </div>

                            <!-- List of Established Keyframes -->
                            <div class="space-y-1.5 max-h-48 overflow-y-auto pr-1">
                                @forelse ($boardKeyframes as $kIdx => $kf)
                                    <div class="flex items-center justify-between rounded-lg border border-zinc-200 bg-zinc-50 p-2 text-xs dark:border-zinc-800 dark:bg-zinc-950">
                                        <div class="flex items-center gap-2">
                                            <span class="rounded bg-amber-500 px-1.5 py-0.5 font-bold text-[10px] text-black">
                                                {{ $kf['time_pct'] }}%
                                            </span>
                                            <span class="text-zinc-800 dark:text-zinc-200 font-mono">
                                                Zoom: {{ $kf['zoom'] }}x | Pan: ({{ $kf['x_pct'] }}%, {{ $kf['y_pct'] }}%)
                                                @if (! empty($kf['shake']))
                                                    <span class="text-rose-500 font-bold">| Shake: {{ $kf['shake'] }}</span>
                                                @endif
                                            </span>
                                        </div>
                                        <button type="button" wire:click="removeKeyframe({{ $kIdx }})" class="text-rose-500 hover:text-rose-700">
                                            <flux:icon name="trash" class="h-3.5 w-3.5" />
                                        </button>
                                    </div>
                                @empty
                                    <p class="text-xs text-zinc-500 italic">{{ __('No custom timeline keyframes added. Panel will use standard motion effect.') }}</p>
                                @endforelse
                            </div>
                        </div>

                        <!-- TAB 3: MOTION & PHYSICS CONFIG -->
                        <div x-show="activeTab === 'fx'" class="mt-4 space-y-4">
                            <div>
                                <flux:select wire:model="boardAnimationEffect" label="{{ __('Fallback Motion Movement') }}">
                                    <flux:select.option value="ken_burns">{{ __('Ken Burns (Zoom Pan)') }}</flux:select.option>
                                    <flux:select.option value="parallax">{{ __('Parallax Cinematic Drift') }}</flux:select.option>
                                    <flux:select.option value="slide_in">{{ __('Slide-In Ease') }}</flux:select.option>
                                    <flux:select.option value="camera_shake">{{ __('Camera Shake (Action)') }}</flux:select.option>
                                    <flux:select.option value="impact_frame">{{ __('Impact Frame Flash') }}</flux:select.option>
                                </flux:select>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <flux:select wire:model="boardTransitionType" label="{{ __('Transition') }}">
                                    <flux:select.option value="crossfade">{{ __('Smooth Crossfade') }}</flux:select.option>
                                    <flux:select.option value="cut">{{ __('Hard Manga Cut') }}</flux:select.option>
                                    <flux:select.option value="fade_black">{{ __('Fade to Black') }}</flux:select.option>
                                </flux:select>
                                <flux:select wire:model="boardMoodTag" label="{{ __('Scene Mood (BGM)') }}">
                                    <flux:select.option value="action">{{ __('Action / Battle') }}</flux:select.option>
                                    <flux:select.option value="romance">{{ __('Romance / Gentle') }}</flux:select.option>
                                    <flux:select.option value="suspense">{{ __('Suspense / Tension') }}</flux:select.option>
                                    <flux:select.option value="horror">{{ __('Horror / Dread') }}</flux:select.option>
                                    <flux:select.option value="calm">{{ __('Calm / Ambient') }}</flux:select.option>
                                </flux:select>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <flux:input wire:model="boardGutterSpacing" type="number" label="{{ __('Webtoon Gutter (px)') }}" placeholder="80" />
                                <flux:select wire:model="pageLayoutHint" label="{{ __('Manga Page Layout') }}">
                                    <flux:select.option value="auto">{{ __('Auto Flow') }}</flux:select.option>
                                    <flux:select.option value="splash">{{ __('Full Splash') }}</flux:select.option>
                                    <flux:select.option value="wide_half">{{ __('Wide Half') }}</flux:select.option>
                                    <flux:select.option value="small_quarter">{{ __('Small Cell') }}</flux:select.option>
                                </flux:select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer Actions -->
                <div class="mt-6 flex justify-end gap-3 border-t border-zinc-100 pt-4 dark:border-zinc-800">
                    <flux:button type="button" variant="subtle" wire:click="$set('showBubbleModal', false)">{{ __('Cancel') }}</flux:button>
                    <flux:button type="button" variant="primary" wire:click="saveBoardConfig" class="bg-violet-600 hover:bg-violet-500">
                        {{ __('Save Panel Director Settings') }}
                    </flux:button>
                </div>
            </div>
        </div>
    @endif

    <!-- 1-CLICK STORY / EPUB IMPORT MODAL -->
    @if ($showImportModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4 backdrop-blur-sm" x-data x-on:keydown.escape.window="$wire.set('showImportModal', false)">
            <div class="relative w-full max-w-lg rounded-2xl border border-zinc-200 bg-white p-6 shadow-2xl dark:border-zinc-800 dark:bg-zinc-900">
                <button
                    type="button"
                    wire:click="$set('showImportModal', false)"
                    class="absolute right-4 top-4 rounded-xl p-2 text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800"
                >
                    <flux:icon name="x-mark" class="h-5 w-5" />
                </button>

                <h3 class="text-base font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                    <flux:icon name="arrow-down-tray" class="h-5 w-5 text-violet-500" />
                    {{ __('1-Click Story / EPUB Novel Converter') }}
                </h3>
                <p class="text-xs text-zinc-500 mt-1">{{ __('Transform any existing StoryStudio or EPUB novel chapter into a structured ComicStudio title with speech bubbles and manhwa panels.') }}</p>

                <div class="mt-4 space-y-4">
                    <div>
                        <flux:select wire:model="importSource" label="{{ __('Import Source') }}">
                            <flux:select.option value="story">{{ __('Story Studio (Story Project)') }}</flux:select.option>
                            <flux:select.option value="epub">{{ __('Epub Studio (EPUB Book Chapters)') }}</flux:select.option>
                        </flux:select>
                    </div>

                    @if ($importSource === 'story')
                        <div>
                            <flux:select wire:model="selectedStoryId" label="{{ __('Select Story to Adapt') }}">
                                <flux:select.option value="">-- {{ __('Choose Story') }} --</flux:select.option>
                                @foreach ($availableStories as $s)
                                    <flux:select.option value="{{ $s->id }}">{{ $s->title }} ({{ $s->episodes->count() }} EP)</flux:select.option>
                                @endforeach
                            </flux:select>
                        </div>
                    @else
                        <div>
                            <flux:select wire:model="selectedEpubId" label="{{ __('Select EPUB Project to Adapt') }}">
                                <flux:select.option value="">-- {{ __('Choose EPUB Novel') }} --</flux:select.option>
                                @foreach ($availableEpubs as $e)
                                    <flux:select.option value="{{ $e->id }}">{{ $e->title }} ({{ $e->scenes->count() }} scenes)</flux:select.option>
                                @endforeach
                            </flux:select>
                        </div>
                    @endif
                </div>

                <div class="mt-6 flex justify-end gap-3 border-t border-zinc-100 pt-4 dark:border-zinc-800">
                    <flux:button type="button" variant="subtle" wire:click="$set('showImportModal', false)">{{ __('Cancel') }}</flux:button>
                    <flux:button type="button" variant="primary" wire:click="importFromStoryOrEpub" class="bg-violet-600 hover:bg-violet-500">
                        {{ __('Convert to Comic Studio') }}
                    </flux:button>
                </div>
            </div>
        </div>
    @endif
</div>
