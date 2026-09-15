<div class="w-full space-y-6" wire:poll.4s>
    <!-- Header -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-2">
                <flux:heading size="xl" level="1">{{ __('EPUB & HTML to Video Studio') }}</flux:heading>
                <flux:badge color="indigo" size="sm">Antigravity IDE</flux:badge>
            </div>
            <flux:text class="mt-1 text-zinc-500 dark:text-zinc-400">
                {{ __('Automatically convert EPUB storytelling books and HTML articles into animated video episodes with TTS voiceover and Ken Burns pan/zoom.') }}
            </flux:text>
        </div>
        <div class="flex items-center gap-2">
            <flux:button variant="primary" icon="arrow-up-tray" wire:click="openUploadModal">
                {{ __('Upload EPUB / HTML') }}
            </flux:button>
        </div>
    </div>

    <!-- Main Workspace Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Left Column: Projects List -->
        <div class="lg:col-span-4 space-y-4">
            <div class="flex items-center justify-between">
                <flux:heading size="lg">{{ __('EPUB Projects') }}</flux:heading>
                <flux:badge color="zinc">{{ $projects->count() }}</flux:badge>
            </div>

            @if($projects->isEmpty())
                <div class="rounded-xl border border-dashed border-zinc-300 dark:border-zinc-700 p-8 text-center bg-zinc-50/50 dark:bg-zinc-900/50">
                    <flux:icon icon="book-open" class="mx-auto size-10 text-zinc-400 mb-2" />
                    <flux:heading size="sm">{{ __('No projects yet') }}</flux:heading>
                    <flux:text class="text-xs text-zinc-500 mt-1">{{ __('Upload your first .epub or .html file to start the automated video pipeline.') }}</flux:text>
                    <flux:button size="sm" variant="primary" class="mt-4" wire:click="openUploadModal">
                        {{ __('Upload Book / HTML') }}
                    </flux:button>
                </div>
            @else
                <div class="space-y-3">
                    @foreach($projects as $p)
                        <div
                            wire:key="project-{{ $p->id }}"
                            wire:click="selectProject({{ $p->id }})"
                            class="p-4 rounded-xl border cursor-pointer transition-all {{ $activeProject && $activeProject->id === $p->id ? 'border-indigo-500 bg-indigo-50/30 dark:bg-indigo-950/20 shadow-sm ring-1 ring-indigo-500/20' : 'border-zinc-200 dark:border-zinc-700/80 hover:border-zinc-300 dark:hover:border-zinc-600 bg-white dark:bg-zinc-900' }}"
                        >
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <h3 class="font-semibold text-sm text-zinc-900 dark:text-zinc-100 truncate">{{ $p->title }}</h3>
                                    <div class="flex items-center gap-2 mt-1 text-xs text-zinc-500">
                                        <span>{{ $p->aspect_ratio }}</span>
                                        <span>•</span>
                                        <span>{{ $p->created_at->diffForHumans() }}</span>
                                    </div>
                                </div>
                                <div>
                                    @if($p->status === 'completed')
                                        <flux:badge color="emerald" size="sm">{{ __('Completed') }}</flux:badge>
                                    @elseif($p->status === 'failed')
                                        <flux:badge color="red" size="sm">{{ __('Failed') }}</flux:badge>
                                    @else
                                        <flux:badge color="amber" size="sm" class="animate-pulse">
                                            {{ ucfirst(str_replace('_', ' ', $p->status)) }}
                                        </flux:badge>
                                    @endif
                                </div>
                            </div>

                            <!-- Progress bar -->
                            <div class="mt-3">
                                <div class="flex items-center justify-between text-[11px] text-zinc-500 mb-1">
                                    <span>{{ $p->rendered_scenes }} / {{ $p->total_scenes }} {{ __('scenes rendered') }}</span>
                                    <span>{{ $p->progress_percent }}%</span>
                                </div>
                                <div class="w-full h-1.5 bg-zinc-100 dark:bg-zinc-800 rounded-full overflow-hidden">
                                    <div
                                        class="h-full bg-indigo-500 transition-all duration-300 rounded-full {{ $p->status === 'completed' ? 'bg-emerald-500' : '' }}"
                                        style="width: {{ $p->progress_percent }}%"
                                    ></div>
                                </div>
                            </div>

                            <div class="flex items-center justify-between mt-3 pt-2 border-t border-zinc-100 dark:border-zinc-800/80">
                                @if($p->status === 'failed')
                                    <button
                                        type="button"
                                        wire:click.stop="retryProject({{ $p->id }})"
                                        class="text-xs text-amber-600 hover:text-amber-700 dark:text-amber-400 flex items-center gap-1 font-medium"
                                    >
                                        <flux:icon icon="arrow-path" class="size-3" /> {{ __('Retry') }}
                                    </button>
                                @else
                                    <span class="text-[11px] text-zinc-400">{{ $p->voice_name }}</span>
                                @endif

                                <button
                                    type="button"
                                    wire:click.stop="deleteProject({{ $p->id }})"
                                    wire:confirm="{{ __('Are you sure you want to delete this EPUB project?') }}"
                                    class="text-xs text-zinc-400 hover:text-red-500 transition-colors p-1"
                                >
                                    <flux:icon icon="trash" class="size-3.5" />
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Right Column: Active Project Inspector & Scenes Breakdown -->
        <div class="lg:col-span-8 space-y-6">
            @if(! $activeProject)
                <div class="rounded-2xl border border-dashed border-zinc-300 dark:border-zinc-700 p-12 text-center bg-zinc-50 dark:bg-zinc-900/50">
                    <flux:icon icon="film" class="mx-auto size-12 text-zinc-400 mb-3" />
                    <flux:heading size="lg">{{ __('Select an EPUB Project') }}</flux:heading>
                    <flux:text class="text-zinc-500 mt-1 max-w-md mx-auto">
                        {{ __('Choose a project from the left panel to inspect scenes, view audio/video clips, or download the final merged episode.') }}
                    </flux:text>
                </div>
            @else
                <!-- Active Project Overview Card -->
                <div class="p-6 rounded-2xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div>
                            <div class="flex items-center gap-2">
                                <flux:heading size="lg">{{ $activeProject->title }}</flux:heading>
                                <flux:badge color="{{ $activeProject->status === 'completed' ? 'emerald' : ($activeProject->status === 'failed' ? 'red' : 'amber') }}">
                                    {{ ucfirst(str_replace('_', ' ', $activeProject->status)) }}
                                </flux:badge>
                            </div>
                            <flux:text class="text-xs text-zinc-500 mt-1">
                                {{ __('Aspect Ratio:') }} <strong class="text-zinc-700 dark:text-zinc-300">{{ $activeProject->aspect_ratio }}</strong> •
                                {{ __('Translation:') }} <strong class="text-zinc-700 dark:text-zinc-300">{{ strtoupper($activeProject->source_language ?: 'AUTO') }} ➔ {{ strtoupper($activeProject->target_language ?: 'EN') }}</strong> •
                                {{ __('Voice:') }} <strong class="text-zinc-700 dark:text-zinc-300">{{ $activeProject->voice_name }}</strong> •
                                {{ __('Total Scenes:') }} <strong class="text-zinc-700 dark:text-zinc-300">{{ $activeProject->total_scenes }}</strong>
                            </flux:text>
                        </div>

                        <div class="flex items-center gap-2">
                            @if($activeProject->scenes->where('status', 'completed')->count() > 0)
                                <flux:button
                                    size="sm"
                                    variant="filled"
                                    icon="sparkles"
                                    wire:click="remergeEpisode({{ $activeProject->id }})"
                                    title="{{ __('Re-merge all completed scenes into final episode video') }}"
                                >
                                    {{ __('Regenerate Full Video') }}
                                </flux:button>
                            @endif

                            @if($activeProject->output_video_url)
                                <a
                                    href="{{ $activeProject->output_video_url }}"
                                    download="episode_{{ $activeProject->id }}.mp4"
                                    target="_blank"
                                    class="inline-flex items-center gap-2 px-3 py-1.5 text-xs font-medium rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white shadow-sm transition-colors"
                                >
                                    <flux:icon icon="arrow-down-tray" class="size-4" />
                                    {{ __('Download Final Video') }}
                                </a>
                            @endif

                            @if($activeProject->status === 'failed')
                                <flux:button size="sm" variant="danger" icon="arrow-path" wire:click="retryProject({{ $activeProject->id }})">
                                    {{ __('Retry Project Pipeline') }}
                                </flux:button>
                            @endif
                        </div>
                    </div>

                    @if($activeProject->error_message)
                        <div class="p-3 rounded-lg bg-red-50 dark:bg-red-950/30 border border-red-200 dark:border-red-900 text-xs text-red-700 dark:text-red-400">
                            <strong>{{ __('Error:') }}</strong> {{ $activeProject->error_message }}
                        </div>
                    @endif

                    <!-- Final Episode Video Player (when ready) -->
                    @if($activeProject->output_video_url)
                        <div class="pt-2">
                            <flux:heading size="sm" class="mb-2">{{ __('Final Merged Episode Video') }}</flux:heading>
                            <div class="rounded-xl overflow-hidden bg-black aspect-video max-h-96 flex items-center justify-center">
                                <video
                                    src="{{ $activeProject->output_video_url }}"
                                    controls
                                    class="w-full h-full object-contain"
                                ></video>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Scene Breakdown Section -->
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <flux:heading size="lg">{{ __('Extracted Scenes & Media Clips') }}</flux:heading>
                        <flux:text class="text-xs text-zinc-500">
                            {{ $activeProject->scenes->where('status', 'completed')->count() }} / {{ $activeProject->scenes->count() }} {{ __('Rendered') }}
                        </flux:text>
                    </div>

                    @if($activeProject->scenes->isEmpty())
                        <div class="p-8 rounded-xl border border-dashed border-zinc-300 dark:border-zinc-700 text-center bg-zinc-50 dark:bg-zinc-900/50">
                            <flux:icon icon="clock" class="mx-auto size-8 text-zinc-400 mb-2 animate-spin" />
                            <flux:text>{{ __('Extracting scenes from EPUB in the background...') }}</flux:text>
                        </div>
                    @else
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach($activeProject->scenes as $scene)
                                <div
                                    wire:key="scene-{{ $scene->id }}"
                                    class="p-4 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-sm flex flex-col justify-between space-y-3"
                                >
                                    <div>
                                        <!-- Header & Status -->
                                        <div class="flex items-center justify-between mb-2">
                                            <div class="flex items-center gap-2">
                                                <span class="font-bold text-xs text-zinc-700 dark:text-zinc-300">
                                                    {{ __('Scene #') }}{{ $scene->sequence_order }}
                                                </span>
                                                <button
                                                    type="button"
                                                    wire:click="deleteScene({{ $scene->id }})"
                                                    wire:confirm="{{ __('Are you sure you want to delete Scene #:order?', ['order' => $scene->sequence_order]) }}"
                                                    class="text-zinc-400 hover:text-red-500 dark:hover:text-red-400 transition-colors p-0.5 rounded hover:bg-red-50 dark:hover:bg-red-950/30"
                                                    title="{{ __('Delete Scene') }}"
                                                >
                                                    <flux:icon icon="trash" class="size-3.5" />
                                                </button>
                                            </div>
                                            <div>
                                                @if($scene->status === 'completed')
                                                    <flux:badge color="emerald" size="sm">✓ {{ __('Video Ready') }}</flux:badge>
                                                @elseif($scene->status === 'failed')
                                                    <flux:badge color="red" size="sm">{{ __('Failed') }}</flux:badge>
                                                @elseif($scene->status === 'rendering_video')
                                                    <flux:badge color="indigo" size="sm" class="animate-pulse">{{ __('Rendering Video...') }}</flux:badge>
                                                @elseif($scene->status === 'generating_audio')
                                                    <flux:badge color="amber" size="sm" class="animate-pulse">{{ __('Generating TTS...') }}</flux:badge>
                                                @elseif($scene->status === 'audio_ready')
                                                    <flux:badge color="blue" size="sm">● {{ __('Image Updated • Ready') }}</flux:badge>
                                                @else
                                                    <flux:badge color="zinc" size="sm">{{ ucfirst(str_replace('_', ' ', $scene->status)) }}</flux:badge>
                                                @endif
                                            </div>
                                        </div>

                                        <!-- Image / Video Preview Box -->
                                        <div class="rounded-lg overflow-hidden bg-zinc-950 aspect-video mb-3 relative flex items-center justify-center group">
                                            @if($scene->video_url)
                                                <video
                                                    src="{{ $scene->video_url }}"
                                                    controls
                                                    class="w-full h-full object-cover"
                                                ></video>
                                            @elseif($scene->image_url)
                                                <img
                                                    src="{{ $scene->image_url }}"
                                                    alt="Scene #{{ $scene->sequence_order }}"
                                                    class="w-full h-full object-cover"
                                                />
                                            @else
                                                <div class="text-zinc-500 text-xs flex flex-col items-center gap-1">
                                                    <flux:icon icon="photo" class="size-6" />
                                                    <span>{{ __('No Image Extracted') }}</span>
                                                </div>
                                            @endif

                                            <!-- Upload Loading Overlay -->
                                            <div
                                                wire:loading
                                                wire:target="sceneImageFiles.{{ $scene->id }}"
                                                class="absolute inset-0 bg-black/75 backdrop-blur-xs flex flex-col items-center justify-center gap-1 text-white text-xs z-20"
                                            >
                                                <flux:icon icon="arrow-path" class="size-5 animate-spin text-indigo-400" />
                                                <span>{{ __('Uploading Image...') }}</span>
                                            </div>

                                            <!-- Image Action Buttons (Change Custom Image & Reset) -->
                                            <div class="absolute top-2 right-2 flex items-center gap-1 z-10 opacity-90 group-hover:opacity-100 transition-opacity">
                                                @if($scene->original_image_path && $scene->image_path !== $scene->original_image_path)
                                                    <button
                                                        type="button"
                                                        wire:click="resetSceneImage({{ $scene->id }})"
                                                        class="px-2 py-1 text-[10px] font-medium rounded-md bg-zinc-900/80 hover:bg-zinc-900 text-zinc-300 hover:text-white border border-zinc-700 shadow-sm backdrop-blur-xs flex items-center gap-1 transition-all"
                                                        title="{{ __('Reset to original eBook illustration') }}"
                                                    >
                                                        <flux:icon icon="arrow-uturn-left" class="size-3" />
                                                        <span>{{ __('Reset') }}</span>
                                                    </button>
                                                @endif

                                                <label
                                                    class="px-2 py-1 text-[10px] font-medium rounded-md bg-black/75 hover:bg-black text-white border border-white/20 shadow-sm backdrop-blur-xs cursor-pointer flex items-center gap-1 transition-all hover:border-indigo-400"
                                                    title="{{ __('Upload custom illustration for this scene') }}"
                                                >
                                                    <flux:icon icon="photo" class="size-3 text-indigo-400" />
                                                    <span>{{ $scene->image_path ? __('Change Image') : __('Upload Image') }}</span>
                                                    <input
                                                        type="file"
                                                        wire:model="sceneImageFiles.{{ $scene->id }}"
                                                        accept="image/*"
                                                        class="hidden"
                                                    />
                                                </label>
                                            </div>

                                            @if($scene->audio_duration)
                                                <div class="absolute bottom-2 right-2 px-1.5 py-0.5 rounded bg-black/70 text-[10px] text-white font-mono z-10">
                                                    {{ number_format($scene->audio_duration, 1) }}s
                                                </div>
                                            @endif
                                        </div>

                                        <!-- Script Text Paragraph (Translated / Primary) -->
                                        <p class="text-xs text-zinc-700 dark:text-zinc-200 line-clamp-3 leading-relaxed font-medium">
                                            {{ $scene->script_text }}
                                        </p>

                                        @if($scene->original_script_text && $scene->original_script_text !== $scene->script_text)
                                            <div x-data="{ showOriginal: false }" class="mt-1">
                                                <button
                                                    type="button"
                                                    @click="showOriginal = !showOriginal"
                                                    class="text-[10px] text-zinc-400 hover:text-indigo-500 dark:hover:text-indigo-400 flex items-center gap-1 transition-colors"
                                                >
                                                    <flux:icon icon="language" class="size-3" />
                                                    <span x-text="showOriginal ? '{{ __('Hide Original Text') }}' : '{{ __('Show Original Text') }}'"></span>
                                                </button>
                                                <div x-show="showOriginal" x-cloak class="text-[11px] text-zinc-500 dark:text-zinc-400 italic mt-1 bg-zinc-50 dark:bg-zinc-800/60 p-2 rounded-lg border border-zinc-100 dark:border-zinc-800 leading-relaxed">
                                                    {{ $scene->original_script_text }}
                                                </div>
                                            </div>
                                        @endif

                                        @if($scene->status === 'failed' && $scene->error_message)
                                            <div class="mt-2 rounded-lg bg-red-50 dark:bg-red-950/30 border border-red-200 dark:border-red-900 p-2 text-[11px] text-red-700 dark:text-red-400">
                                                <div class="flex items-start justify-between gap-1">
                                                    <span class="line-clamp-2">⚠️ {{ $scene->error_message }}</span>
                                                    <button 
                                                        type="button" 
                                                        wire:click="retryScene({{ $scene->id }})" 
                                                        class="shrink-0 font-bold underline hover:text-red-900 dark:hover:text-red-300 ml-1"
                                                    >
                                                        {{ __('Retry') }}
                                                    </button>
                                                </div>
                                            </div>
                                        @endif
                                    </div>

                                    <!-- Audio Player & Regenerate Controls -->
                                    <div class="pt-2 border-t border-zinc-100 dark:border-zinc-800/80 flex items-center justify-between gap-2">
                                        @if($scene->audio_url)
                                            <audio src="{{ $scene->audio_url }}" controls class="h-7 w-full max-w-[180px]"></audio>
                                        @else
                                            <span class="text-[10px] text-zinc-400">{{ __('Audio generating...') }}</span>
                                        @endif

                                        <div class="flex items-center gap-1.5">
                                            <button
                                                type="button"
                                                onclick="triggerSwalRegenerateScene({{ $scene->id }}, {{ $scene->sequence_order }}, {{ $scene->audio_path ? 'true' : 'false' }})"
                                                class="text-xs text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 font-medium flex items-center gap-1 transition-colors px-2 py-1 rounded-lg hover:bg-indigo-50 dark:hover:bg-indigo-950/40"
                                                title="{{ __('Regenerate this scene (Video only or All with TTS)') }}"
                                            >
                                                <flux:icon icon="arrow-path" class="size-3.5" />
                                                <span>{{ __('Regenerate') }}</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>

    <!-- Upload EPUB Modal -->
    <flux:modal wire:model="showUploadModal" class="max-w-lg">
        <form wire:submit.prevent="uploadEpub" class="space-y-4">
            <div>
                <flux:heading size="lg">{{ __('Upload EPUB / HTML Storytelling Document') }}</flux:heading>
                <flux:text class="text-xs text-zinc-500 mt-1">
                    {{ __('Upload an .epub, .html, .htm, .xhtml file or .zip package. The system will extract text paragraphs, translate if specified, pair with illustrations, synthesize audio, and render video clips.') }}
                </flux:text>
            </div>

            <flux:input
                wire:model="projectTitle"
                :label="__('Project Title')"
                placeholder="e.g., The Shadow Over Innsmouth"
                required
            />

            <div>
                <flux:label>{{ __('Book / Document File (.epub, .html, .htm, .xhtml, .zip)') }}</flux:label>
                <input
                    type="file"
                    wire:model="epubFile"
                    accept=".epub,.html,.htm,.xhtml,.zip"
                    class="block w-full text-xs text-zinc-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-zinc-800 dark:file:text-zinc-300 mt-1 border border-zinc-300 dark:border-zinc-700 rounded-lg p-2"
                    required
                />
                @error('epubFile') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <flux:select wire:model="sourceLanguage" :label="__('Source Language (Book)')">
                        @foreach($supportedLanguages as $code => $name)
                            <flux:select.option value="{{ $code }}">{{ $name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <div>
                    <flux:select wire:model.live="targetLanguage" :label="__('Target Language (Translate To)')">
                        @foreach($supportedLanguages as $code => $name)
                            @if($code !== 'auto')
                                <flux:select.option value="{{ $code }}">{{ $name }}</flux:select.option>
                            @endif
                        @endforeach
                    </flux:select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <flux:select wire:model="aspectRatio" :label="__('Video Aspect Ratio')">
                        <flux:select.option value="16:9">{{ __('16:9 Landscape (YouTube)') }}</flux:select.option>
                        <flux:select.option value="9:16">{{ __('9:16 Vertical (Shorts/Reels)') }}</flux:select.option>
                    </flux:select>
                </div>

                <div>
                    <flux:select wire:model="voiceName" :label="__('TTS Voiceover')">
                        @foreach($groupedVoices as $langGroup => $voices)
                            <optgroup label="{{ strtoupper($langGroup) }} Voices">
                                @foreach($voices as $voiceVal => $voiceLabel)
                                    <flux:select.option value="{{ $voiceVal }}">{{ $voiceLabel }}</flux:select.option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </flux:select>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-zinc-100 dark:border-zinc-800">
                <flux:button type="button" variant="ghost" wire:click="$set('showUploadModal', false)">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="uploadEpub">{{ __('Start Video Processing') }}</span>
                    <span wire:loading wire:target="uploadEpub">{{ __('Uploading & Queuing...') }}</span>
                </flux:button>
            </div>
        </form>
    </flux:modal>

    <script>
        function triggerSwalRegenerateScene(sceneId, sceneOrder, hasAudio) {
            if (typeof Swal === 'undefined') {
                if (confirm('Regenerate Scene #' + sceneOrder + '? Click OK for Video Only (keep audio), Cancel to cancel.')) {
                    @this.call('regenerateScene', sceneId, hasAudio ? 'video_only' : 'all_with_tts');
                }
                return;
            }

            Swal.fire({
                title: '⚡ Regenerate Scene #' + sceneOrder,
                html: `
                    <p class="text-xs text-zinc-400 mb-4 text-left leading-relaxed">
                        Choose the regeneration scope for this scene. You can quickly re-render the video clip with latest subtitles and motion directives, or re-synthesize TTS narration.
                    </p>
                    <div class="text-left space-y-2.5 text-xs">
                        <label class="flex items-center gap-3 cursor-pointer p-3 rounded-xl bg-zinc-800/90 border border-zinc-700 hover:border-indigo-500 transition-all">
                            <input type="radio" name="swal_scene_regen_mode" value="video_only" checked class="text-indigo-600 focus:ring-indigo-500">
                            <div>
                                <span class="block font-bold text-zinc-100">🎬 Generate Video Only</span>
                                <span class="block text-[11px] text-zinc-400">Keep existing TTS audio and re-render the MP4 clip with burned subtitles & motion directives.</span>
                            </div>
                        </label>
                        <label class="flex items-center gap-3 cursor-pointer p-3 rounded-xl bg-zinc-800/90 border border-zinc-700 hover:border-amber-500 transition-all">
                            <input type="radio" name="swal_scene_regen_mode" value="all_with_tts" class="text-amber-600 focus:ring-amber-500">
                            <div>
                                <span class="block font-bold text-zinc-100">🎙️ All with TTS (Full Re-render)</span>
                                <span class="block text-[11px] text-zinc-400">Re-synthesize fresh voiceover audio from scratch, then re-render the video clip.</span>
                            </div>
                        </label>
                    </div>
                `,
                icon: 'question',
                iconColor: '#6366f1',
                showCancelButton: true,
                confirmButtonColor: '#4f46e5',
                cancelButtonColor: '#3f3f46',
                confirmButtonText: '🚀 Start Regeneration',
                cancelButtonText: 'Cancel',
                background: '#18181b',
                color: '#f4f4f5',
                customClass: {
                    popup: 'rounded-2xl border border-zinc-700 shadow-2xl',
                    confirmButton: 'rounded-xl font-bold px-4 py-2.5 text-xs',
                    cancelButton: 'rounded-xl font-bold px-4 py-2.5 text-xs',
                },
                preConfirm: () => {
                    const selected = document.querySelector('input[name="swal_scene_regen_mode"]:checked');
                    return selected ? selected.value : 'video_only';
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    @this.call('regenerateScene', sceneId, result.value);
                }
            });
        }
    </script>
</div>
