<div class="w-full space-y-6">
    <!-- Hero Banner -->
    <div class="relative overflow-hidden rounded-2xl border border-zinc-200 bg-gradient-to-br from-zinc-900 via-indigo-950 to-zinc-900 p-8 text-white shadow-xl dark:border-zinc-800">
        <div class="absolute -right-16 -top-16 h-64 w-64 rounded-full bg-indigo-500/20 blur-3xl"></div>
        <div class="absolute -bottom-16 -left-16 h-64 w-64 rounded-full bg-purple-500/20 blur-3xl"></div>
        <div class="absolute right-1/3 top-1/2 h-32 w-32 -translate-y-1/2 rounded-full bg-pink-500/10 blur-2xl"></div>

        <div class="relative z-10 flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
            <div class="max-w-2xl space-y-3">
                <div class="inline-flex items-center gap-2 rounded-full border border-indigo-400/30 bg-indigo-500/10 px-3 py-1 text-xs font-semibold text-indigo-300 backdrop-blur-sm">
                    <flux:icon name="sparkles" class="h-3.5 w-3.5 text-amber-400" />
                    {{ __('Monetizable Video Factory') }}
                </div>
                <h1 class="text-3xl font-extrabold tracking-tight sm:text-4xl text-white">
                    {{ __('Turn Video Niches Into Revenue') }}
                </h1>
                <p class="text-sm leading-relaxed text-zinc-300 sm:text-base">
                    {{ __('Curated production blueprints for YouTube AdSense & TikTok monetization. Each blueprint pre-configures prompt engineering, visual styles, Edge-TTS narrators, and aspect ratios to launch high-retention content in minutes.') }}
                </p>
            </div>

            <!-- Arsenal Highlights -->
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-2">
                <div class="rounded-xl border border-white/10 bg-white/5 p-3 backdrop-blur-sm">
                    <div class="text-xs text-zinc-400">{{ __('Top CPM Niche') }}</div>
                    <div class="text-lg font-bold text-emerald-400">$20+ RPM</div>
                    <div class="text-[10px] text-zinc-400">{{ __('Science Explainer') }}</div>
                </div>
                <div class="rounded-xl border border-white/10 bg-white/5 p-3 backdrop-blur-sm">
                    <div class="text-xs text-zinc-400">{{ __('Viral TikTok Shop') }}</div>
                    <div class="text-lg font-bold text-amber-400">{{ __('Kids Books') }}</div>
                    <div class="text-[10px] text-zinc-400">{{ __('Yellow Cart Sales') }}</div>
                </div>
                <div class="rounded-xl border border-white/10 bg-white/5 p-3 backdrop-blur-sm">
                    <div class="text-xs text-zinc-400">{{ __('Watch-Time King') }}</div>
                    <div class="text-lg font-bold text-indigo-400">{{ __('Manhwa') }}</div>
                    <div class="text-[10px] text-zinc-400">{{ __('Mid-roll Ad Loops') }}</div>
                </div>
                <div class="rounded-xl border border-white/10 bg-white/5 p-3 backdrop-blur-sm">
                    <div class="text-xs text-zinc-400">{{ __('Dual Revenue') }}</div>
                    <div class="text-lg font-bold text-pink-400">{{ __('Affiliate') }}</div>
                    <div class="text-[10px] text-zinc-400">{{ __('Amazon Book Links') }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters & Search Toolbar -->
    <div class="space-y-4 rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <!-- Niche Filter Tabs -->
        <div class="flex flex-wrap items-center gap-2">
            @foreach ($niches as $key => $nicheData)
                <button
                    type="button"
                    wire:click="selectNiche('{{ $key }}')"
                    class="inline-flex items-center gap-2 rounded-xl px-4 py-2 text-xs font-semibold transition-all {{ $niche === $key ? 'bg-zinc-900 text-white shadow-sm dark:bg-white dark:text-zinc-900' : 'bg-zinc-100 text-zinc-600 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-400 dark:hover:bg-zinc-700' }}"
                >
                    <flux:icon name="{{ $nicheData['icon'] }}" class="h-3.5 w-3.5" />
                    <span>{{ __($nicheData['label']) }}</span>
                    <span class="rounded-full px-1.5 py-0.5 text-[10px] {{ $niche === $key ? 'bg-zinc-700 text-white dark:bg-zinc-200 dark:text-zinc-900' : 'bg-zinc-200 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-300' }}">
                        {{ $nicheData['count'] }}
                    </span>
                </button>
            @endforeach
        </div>

        <!-- Secondary Controls: Search & Target Platform -->
        <div class="flex flex-col gap-3 pt-2 sm:flex-row sm:items-center sm:justify-between">
            <div class="relative w-full max-w-md">
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="{{ __('Search blueprint name, visual style, keywords...') }}"
                    class="w-full rounded-xl border border-zinc-300 bg-zinc-50 px-3.5 py-2 text-xs text-zinc-900 placeholder-zinc-400 transition-colors focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:placeholder-zinc-500 dark:focus:border-indigo-400 dark:focus:bg-zinc-900"
                />
            </div>

            <!-- Platform Selector -->
            <div class="flex items-center gap-1 self-end sm:self-auto">
                <span class="mr-2 text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('Platform:') }}</span>
                @foreach (['all' => 'All Formats', 'youtube' => 'YouTube 16:9', 'tiktok' => 'TikTok 9:16'] as $platKey => $platLabel)
                    <button
                        type="button"
                        wire:click="selectPlatform('{{ $platKey }}')"
                        class="rounded-lg px-2.5 py-1 text-xs font-medium transition-all {{ $platform === $platKey ? 'bg-indigo-600 text-white dark:bg-indigo-500' : 'bg-zinc-100 text-zinc-600 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-400' }}"
                    >
                        {{ __($platLabel) }}
                    </button>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Template Blueprints Grid -->
    @if ($templates->isEmpty())
        <div class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-zinc-300 p-12 text-center dark:border-zinc-700">
            <flux:icon name="magnifying-glass" class="h-10 w-10 text-zinc-400" />
            <h3 class="mt-3 text-base font-semibold text-zinc-900 dark:text-zinc-100">{{ __('No blueprints match your filter') }}</h3>
            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Try clearing your search query or selecting "All Niches".') }}</p>
            <button
                type="button"
                wire:click="$set('search', ''); $set('niche', 'all'); $set('platform', 'all')"
                class="mt-4 rounded-lg bg-zinc-900 px-4 py-2 text-xs font-medium text-white hover:bg-zinc-800 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-200"
            >
                {{ __('Reset Filters') }}
            </button>
        </div>
    @else
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-2">
            @foreach ($templates as $template)
                <div class="group relative flex flex-col justify-between overflow-hidden rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm transition-all duration-200 hover:-translate-y-1 hover:border-indigo-400/50 hover:shadow-lg dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-indigo-500/40">
                    <div>
                        <!-- Header Badges -->
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center gap-2">
                                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/40 dark:text-indigo-400">
                                    <flux:icon name="{{ $template->icon }}" class="h-5 w-5" />
                                </div>
                                <div>
                                    <span class="inline-block rounded-md bg-zinc-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400">
                                        {{ str_replace('_', ' ', $template->niche) }}
                                    </span>
                                </div>
                            </div>

                            <!-- Estimated RPM Pill -->
                            @if ($template->estimated_rpm_min)
                                <div class="inline-flex items-center gap-1 rounded-full border border-emerald-500/30 bg-emerald-500/10 px-2.5 py-0.5 text-xs font-bold text-emerald-700 dark:text-emerald-400">
                                    <flux:icon name="currency-dollar" class="h-3 w-3" />
                                    <span>${{ number_format($template->estimated_rpm_min, 1) }} - ${{ number_format($template->estimated_rpm_max, 1) }} RPM</span>
                                </div>
                            @endif
                        </div>

                        <!-- Title & Description -->
                        <h3 class="mt-4 text-base font-bold text-zinc-900 dark:text-zinc-100 group-hover:text-indigo-600 dark:group-hover:text-indigo-400">
                            {{ $template->name }}
                        </h3>
                        <p class="mt-2 text-xs leading-relaxed text-zinc-600 dark:text-zinc-400 line-clamp-3">
                            {{ $template->description }}
                        </p>

                        <!-- Key Specs Row -->
                        <div class="mt-4 flex flex-wrap items-center gap-2 pt-2 text-[11px] text-zinc-500 dark:text-zinc-400">
                            <!-- Aspect Ratio & Platform -->
                            <span class="inline-flex items-center gap-1 rounded-md bg-zinc-100 px-2 py-1 font-medium dark:bg-zinc-800">
                                <flux:icon name="{{ $template->aspect_ratio === '9:16' ? 'device-phone-mobile' : 'tv' }}" class="h-3.5 w-3.5 text-zinc-400" />
                                {{ $template->aspect_ratio }} ({{ ucfirst($template->target_platform) }})
                            </span>

                            <!-- Voice -->
                            <span class="inline-flex items-center gap-1 rounded-md bg-zinc-100 px-2 py-1 font-medium dark:bg-zinc-800">
                                <flux:icon name="speaker-wave" class="h-3.5 w-3.5 text-zinc-400" />
                                {{ explode('-', $template->voice_name)[2] ?? $template->voice_name }}
                            </span>

                            <!-- Visual Style Snippet -->
                            <span class="inline-flex items-center gap-1 rounded-md bg-indigo-50 px-2 py-1 font-medium text-indigo-700 dark:bg-indigo-950/30 dark:text-indigo-300">
                                <flux:icon name="paint-brush" class="h-3.5 w-3.5" />
                                {{ Str::limit($template->visual_style, 32) }}
                            </span>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="mt-6 flex flex-col gap-2 border-t border-zinc-100 pt-4 sm:flex-row sm:items-center sm:justify-between dark:border-zinc-800">
                        <!-- Strategy Preview Button -->
                        <button
                            type="button"
                            wire:click="openPreview({{ $template->id }})"
                            class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-zinc-300 bg-white px-3 py-2 text-xs font-semibold text-zinc-700 transition hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700"
                        >
                            <flux:icon name="information-circle" class="h-4 w-4 text-indigo-500" />
                            <span>{{ __('Strategy & Tips') }}</span>
                        </button>

                        <div class="flex items-center gap-2">
                            <!-- Use in EPUB Studio (For Books/Stories) -->
                            @if ($template->content_type === 'motion_comic' || in_array($template->niche, ['manhwa_recap', 'horror_narration']))
                                <button
                                    type="button"
                                    wire:click="useInComicStudio({{ $template->id }})"
                                    title="{{ __('Launch Motion Comic Pipeline') }}"
                                    class="inline-flex items-center justify-center gap-1 rounded-xl border border-violet-300 bg-violet-50 px-3 py-2 text-xs font-semibold text-violet-800 transition hover:bg-violet-100 dark:border-violet-800/40 dark:bg-violet-950/30 dark:text-violet-300 dark:hover:bg-violet-950/60"
                                >
                                    <flux:icon name="film" class="h-4 w-4" />
                                    <span>{{ __('Comic Studio') }}</span>
                                </button>
                            @endif

                            @if (in_array($template->niche, ['kids_story', 'book_summary']))
                                <button
                                    type="button"
                                    wire:click="useInEpubStudio({{ $template->id }})"
                                    title="{{ __('Import Book or HTML into Video') }}"
                                    class="inline-flex items-center justify-center gap-1 rounded-xl border border-emerald-300 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-800 transition hover:bg-emerald-100 dark:border-emerald-800/40 dark:bg-emerald-950/30 dark:text-emerald-300 dark:hover:bg-emerald-950/60"
                                >
                                    <flux:icon name="book-open" class="h-4 w-4" />
                                    <span>{{ __('EPUB Studio') }}</span>
                                </button>
                            @endif

                            <!-- Primary: Use in Story Studio -->
                            <button
                                type="button"
                                wire:click="useInStoryStudio({{ $template->id }})"
                                class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-xl bg-indigo-600 px-4 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-indigo-500 dark:bg-indigo-500 dark:hover:bg-indigo-400 sm:flex-none"
                            >
                                <flux:icon name="rocket-launch" class="h-4 w-4" />
                                <span>{{ __('Launch in Studio') }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <!-- Strategy & Monetization Modal -->
    @if ($showPreviewModal && $previewTemplate)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm" x-data x-on:keydown.escape.window="$wire.closePreview()">
            <div class="relative max-h-[90vh] w-full max-w-3xl overflow-y-auto rounded-2xl border border-zinc-200 bg-white p-6 shadow-2xl dark:border-zinc-800 dark:bg-zinc-900 sm:p-8">
                <!-- Close Button -->
                <button
                    type="button"
                    wire:click="closePreview"
                    class="absolute right-4 top-4 rounded-xl p-2 text-zinc-400 transition hover:bg-zinc-100 hover:text-zinc-600 dark:hover:bg-zinc-800 dark:hover:text-zinc-200"
                >
                    <flux:icon name="x-mark" class="h-5 w-5" />
                </button>

                <!-- Modal Header -->
                <div class="flex items-start gap-4">
                    <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/40 dark:text-indigo-400">
                        <flux:icon name="{{ $previewTemplate->icon }}" class="h-7 w-7" />
                    </div>
                    <div class="space-y-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-md bg-indigo-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-indigo-800 dark:bg-indigo-950/50 dark:text-indigo-300">
                                {{ str_replace('_', ' ', $previewTemplate->niche) }}
                            </span>
                            <span class="rounded-md bg-zinc-100 px-2 py-0.5 text-[10px] font-bold text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400">
                                {{ $previewTemplate->aspect_ratio }} • {{ ucfirst($previewTemplate->target_platform) }}
                            </span>
                            @if ($previewTemplate->estimated_rpm_min)
                                <span class="rounded-md bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-400">
                                    ${{ number_format($previewTemplate->estimated_rpm_min, 1) }} - ${{ number_format($previewTemplate->estimated_rpm_max, 1) }} RPM
                                </span>
                            @endif
                        </div>
                        <h2 class="text-xl font-extrabold text-zinc-900 dark:text-zinc-100">
                            {{ $previewTemplate->name }}
                        </h2>
                    </div>
                </div>

                <p class="mt-4 text-xs leading-relaxed text-zinc-600 dark:text-zinc-300">
                    {{ $previewTemplate->description }}
                </p>

                <!-- Monetization Playbook Section -->
                @if (!empty($previewTemplate->monetization_tips))
                    <div class="mt-6 rounded-2xl border border-amber-300/40 bg-gradient-to-br from-amber-500/5 via-amber-500/10 to-amber-500/5 p-5 dark:border-amber-500/30">
                        <div class="flex items-center gap-2 text-sm font-bold text-amber-800 dark:text-amber-400">
                            <flux:icon name="banknotes" class="h-4 w-4" />
                            <span>{{ __('Monetization & Conversion Playbook') }}</span>
                        </div>
                        <ul class="mt-3 space-y-2.5 text-xs text-zinc-700 dark:text-zinc-300">
                            @foreach ($previewTemplate->monetization_tips as $tip)
                                <li class="flex items-start gap-2">
                                    <flux:icon name="check-circle" class="mt-0.5 h-4 w-4 flex-shrink-0 text-amber-600 dark:text-amber-400" />
                                    <span>{{ $tip }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- Example Titles Inspiration Section -->
                @if (!empty($previewTemplate->example_titles))
                    <div class="mt-6">
                        <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            <flux:icon name="sparkles" class="h-3.5 w-3.5 text-indigo-500" />
                            <span>{{ __('Proven Video Title Concepts (Ready to Produce)') }}</span>
                        </div>
                        <div class="mt-3 space-y-2">
                            @foreach ($previewTemplate->example_titles as $exampleTitle)
                                <div class="flex items-center justify-between gap-3 rounded-xl border border-zinc-200 bg-zinc-50/70 p-3 text-xs text-zinc-800 dark:border-zinc-800 dark:bg-zinc-800/60 dark:text-zinc-200">
                                    <span class="font-medium">{{ $exampleTitle }}</span>
                                    <button
                                        type="button"
                                        x-data="{ copied: false }"
                                        x-on:click="navigator.clipboard.writeText('{{ addslashes($exampleTitle) }}'); copied = true; setTimeout(() => copied = false, 2000)"
                                        class="flex-shrink-0 rounded-md border border-zinc-200 bg-white px-2 py-1 text-[11px] font-semibold text-zinc-600 transition hover:bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-700 dark:text-zinc-300"
                                    >
                                        <span x-show="!copied">{{ __('Copy') }}</span>
                                        <span x-show="copied" class="text-emerald-500">{{ __('Copied!') }}</span>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Generation Blueprint Config Specs -->
                <div class="mt-6 rounded-xl border border-zinc-200 bg-zinc-50 p-4 text-xs dark:border-zinc-800 dark:bg-zinc-800/40">
                    <div class="text-[11px] font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400 mb-3">
                        {{ __('Pre-Configured AI Generation Parameters') }}
                    </div>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <span class="font-semibold text-zinc-700 dark:text-zinc-300">{{ __('Visual Art Style:') }}</span>
                            <p class="mt-0.5 text-zinc-600 dark:text-zinc-400">{{ $previewTemplate->visual_style }}</p>
                        </div>
                        <div>
                            <span class="font-semibold text-zinc-700 dark:text-zinc-300">{{ __('Color Palette & Lighting:') }}</span>
                            <p class="mt-0.5 text-zinc-600 dark:text-zinc-400">{{ $previewTemplate->color_palette }} • {{ $previewTemplate->lighting_mood }}</p>
                        </div>
                        <div>
                            <span class="font-semibold text-zinc-700 dark:text-zinc-300">{{ __('Voice Narrator:') }}</span>
                            <p class="mt-0.5 text-zinc-600 dark:text-zinc-400">{{ $previewTemplate->voice_name }} (Speed: {{ $previewTemplate->tts_rate ?? 'Normal' }}, Pitch: {{ $previewTemplate->tts_pitch ?? 'Default' }})</p>
                        </div>
                        <div>
                            <span class="font-semibold text-zinc-700 dark:text-zinc-300">{{ __('Camera & Framing:') }}</span>
                            <p class="mt-0.5 text-zinc-600 dark:text-zinc-400">{{ $previewTemplate->camera_framing }}</p>
                        </div>
                    </div>
                </div>

                <!-- Footer CTAs -->
                <div class="mt-8 flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-end border-t border-zinc-100 pt-5 dark:border-zinc-800">
                    <button
                        type="button"
                        wire:click="closePreview"
                        class="rounded-xl border border-zinc-300 px-4 py-2 text-xs font-semibold text-zinc-700 hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800"
                    >
                        {{ __('Close') }}
                    </button>

                    @if ($previewTemplate->content_type === 'motion_comic' || in_array($previewTemplate->niche, ['manhwa_recap', 'horror_narration']))
                        <button
                            type="button"
                            wire:click="useInComicStudio({{ $previewTemplate->id }})"
                            class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-violet-400 bg-violet-50 px-4 py-2 text-xs font-semibold text-violet-800 hover:bg-violet-100 dark:border-violet-800/60 dark:bg-violet-950/40 dark:text-violet-300"
                        >
                            <flux:icon name="film" class="h-4 w-4" />
                            <span>{{ __('Launch in Comic Studio') }}</span>
                        </button>
                    @endif

                    @if (in_array($previewTemplate->niche, ['kids_story', 'book_summary']))
                        <button
                            type="button"
                            wire:click="useInEpubStudio({{ $previewTemplate->id }})"
                            class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-emerald-400 bg-emerald-50 px-4 py-2 text-xs font-semibold text-emerald-800 hover:bg-emerald-100 dark:border-emerald-800/60 dark:bg-emerald-950/40 dark:text-emerald-300"
                        >
                            <flux:icon name="book-open" class="h-4 w-4" />
                            <span>{{ __('Import in EPUB Studio') }}</span>
                        </button>
                    @endif

                    <button
                        type="button"
                        wire:click="useInStoryStudio({{ $previewTemplate->id }})"
                        class="inline-flex items-center justify-center gap-1.5 rounded-xl bg-indigo-600 px-5 py-2.5 text-xs font-semibold text-white shadow-sm hover:bg-indigo-500 dark:bg-indigo-500 dark:hover:bg-indigo-400"
                    >
                        <flux:icon name="rocket-launch" class="h-4 w-4" />
                        <span>{{ __('Launch Episode in Story Studio') }}</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
