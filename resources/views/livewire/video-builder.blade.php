<div class="w-full space-y-8" @if($hasActiveJobs) wire:poll.3s @endif>
    <!-- Header Title & Subheading -->
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl" level="1">{{ __('Video Builder') }}</flux:heading>
            <flux:subheading>{{ __('Combine videos, overlay animated stickers, images, and green-screen videos onto your main footage.') }}</flux:subheading>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="flex border-b border-zinc-200 dark:border-zinc-700">
        <button 
            type="button" 
            wire:click="$set('activeTab', 'merger')"
            class="flex items-center gap-2 border-b-2 px-4 py-3 text-sm font-semibold transition-colors duration-150 {{ $activeTab === 'merger' ? 'border-blue-600 text-blue-600 dark:border-blue-400 dark:text-blue-400' : 'border-transparent text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200' }}"
        >
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
            </svg>
            {{ __('Video Merger') }}
        </button>
    </div>

    @if ($activeTab === 'merger')
        <div class="grid grid-cols-1 gap-8 lg:grid-cols-12">
            <!-- Left Column: Source & Asset Configurations (7 cols) -->
            <div class="space-y-6 lg:col-span-7">
                <!-- 1. Main Video Source Card -->
                <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2">
                            <span class="flex h-6 w-6 items-center justify-center rounded-full bg-blue-100 text-xs font-bold text-blue-700 dark:bg-blue-950 dark:text-blue-300">1</span>
                            <h3 class="font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Main Video (Base Canvas)') }}</h3>
                        </div>

                        <!-- Aspect Ratio Switcher -->
                        <div class="flex items-center gap-1 rounded-lg bg-zinc-100 p-1 dark:bg-zinc-800">
                            <button 
                                type="button" 
                                wire:click="$set('aspectRatio', '16:9')"
                                class="rounded px-2.5 py-1 text-xs font-semibold transition-colors {{ $aspectRatio === '16:9' ? 'bg-white text-zinc-900 shadow-xs dark:bg-zinc-700 dark:text-zinc-100' : 'text-zinc-500 hover:text-zinc-800 dark:text-zinc-400' }}"
                            >
                                16:9 {{ __('Landscape') }}
                            </button>
                            <button 
                                type="button" 
                                wire:click="$set('aspectRatio', '9:16')"
                                class="rounded px-2.5 py-1 text-xs font-semibold transition-colors {{ $aspectRatio === '9:16' ? 'bg-white text-zinc-900 shadow-xs dark:bg-zinc-700 dark:text-zinc-100' : 'text-zinc-500 hover:text-zinc-800 dark:text-zinc-400' }}"
                            >
                                9:16 {{ __('Portrait') }}
                            </button>
                        </div>
                    </div>

                    <!-- Source Type Tabs -->
                    <div class="grid grid-cols-3 gap-2 mb-4">
                        <button 
                            type="button" 
                            wire:click="$set('mainSourceType', 'file')"
                            class="rounded-lg border py-2 px-3 text-xs font-medium text-center transition-colors {{ $mainSourceType === 'file' ? 'bg-zinc-900 text-white border-zinc-900 dark:bg-zinc-100 dark:text-zinc-900 dark:border-zinc-100' : 'border-zinc-200 text-zinc-600 hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-400 dark:hover:bg-zinc-800' }}"
                        >
                            {{ __('Upload File') }}
                        </button>
                        <button 
                            type="button" 
                            wire:click="$set('mainSourceType', 'library')"
                            class="rounded-lg border py-2 px-3 text-xs font-medium text-center transition-colors {{ $mainSourceType === 'library' ? 'bg-zinc-900 text-white border-zinc-900 dark:bg-zinc-100 dark:text-zinc-900 dark:border-zinc-100' : 'border-zinc-200 text-zinc-600 hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-400 dark:hover:bg-zinc-800' }}"
                        >
                            {{ __('From Library') }}
                        </button>
                        <button 
                            type="button" 
                            wire:click="$set('mainSourceType', 'url')"
                            class="rounded-lg border py-2 px-3 text-xs font-medium text-center transition-colors {{ $mainSourceType === 'url' ? 'bg-zinc-900 text-white border-zinc-900 dark:bg-zinc-100 dark:text-zinc-900 dark:border-zinc-100' : 'border-zinc-200 text-zinc-600 hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-400 dark:hover:bg-zinc-800' }}"
                        >
                            {{ __('Video URL') }}
                        </button>
                    </div>

                    <!-- Source Input Container -->
                    <div>
                        @if ($mainSourceType === 'file')
                            <div 
                                x-data="{ uploading: false, progress: 0 }"
                                x-on:livewire-upload-start="uploading = true"
                                x-on:livewire-upload-finish="uploading = false; progress = 0"
                                x-on:livewire-upload-error="uploading = false; progress = 0"
                                x-on:livewire-upload-progress="progress = $event.detail.progress"
                                class="relative"
                            >
                                <div class="relative flex flex-col items-center justify-center rounded-lg border-2 border-dashed border-zinc-300 p-6 text-center dark:border-zinc-700 dark:bg-zinc-800/30">
                                    <input 
                                        type="file" 
                                        wire:model="mainVideoFile" 
                                        id="mainVideoFile" 
                                        class="absolute inset-0 z-20 h-full w-full cursor-pointer opacity-0"
                                        accept="video/mp4,video/quicktime,video/x-msvideo,video/webm"
                                    />
                                    <div class="space-y-2" x-show="!uploading">
                                        <svg class="mx-auto h-8 w-8 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                        </svg>
                                        <div class="text-xs text-zinc-600 dark:text-zinc-400">
                                            <span class="font-semibold text-blue-600 dark:text-blue-400">{{ __('Click to choose video') }}</span> {{ __('or drag & drop here') }}
                                        </div>
                                        <p class="text-[10px] text-zinc-400">MP4, MOV, WebM up to 500MB</p>
                                    </div>

                                    <div class="w-full space-y-2" x-show="uploading" x-cloak>
                                        <div class="flex justify-between text-xs text-zinc-500">
                                            <span>{{ __('Uploading video...') }}</span>
                                            <span x-text="progress + '%'"></span>
                                        </div>
                                        <div class="h-1.5 w-full rounded-full bg-zinc-200 dark:bg-zinc-700 overflow-hidden">
                                            <div class="h-full bg-blue-600 transition-all duration-150" :style="'width: ' + progress + '%'"></div>
                                        </div>
                                    </div>
                                </div>

                                @if ($mainVideoFile && !$errors->has('mainVideoFile'))
                                    <div class="mt-2 flex items-center justify-between rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs text-emerald-800 dark:border-emerald-950 dark:bg-emerald-950/30 dark:text-emerald-300">
                                        <span class="truncate font-medium">{{ $mainVideoFile->getClientOriginalName() }}</span>
                                        <span class="shrink-0 text-zinc-500">({{ number_format($mainVideoFile->getSize() / 1024 / 1024, 2) }} MB)</span>
                                    </div>
                                @endif
                                @error('mainVideoFile') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                            </div>
                        @elseif ($mainSourceType === 'library')
                            <div class="space-y-2">
                                <flux:select wire:model.live="selectedLibraryVideoId" :label="__('Select Video from Library')">
                                    <flux:select.option value="">{{ __('-- Choose a previously uploaded video --') }}</flux:select.option>
                                    @foreach ($libraryVideos as $v)
                                        <flux:select.option value="{{ $v->id }}">
                                            {{ $v->title ?: $v->original_filename }} ({{ $v->created_at->format('M d, Y') }})
                                        </flux:select.option>
                                    @endforeach
                                </flux:select>
                                @error('selectedLibraryVideoId') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                            </div>
                        @else
                            <div class="space-y-2">
                                <flux:input 
                                    wire:model="mainVideoUrl" 
                                    :label="__('Video URL')" 
                                    placeholder="https://www.youtube.com/watch?v=... or TikTok link" 
                                />
                                @error('mainVideoUrl') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                            </div>
                        @endif
                    </div>

                    <!-- Custom Project Title -->
                    <div class="mt-4 pt-4 border-t border-zinc-100 dark:border-zinc-800">
                        <flux:input 
                            wire:model="title" 
                            :label="__('Project Title (Optional)')" 
                            placeholder="{{ __('Enter project name or leave blank for automatic name') }}" 
                        />
                    </div>
                </div>

                <!-- 2. Overlay Assets Configuration Card -->
                <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2">
                            <span class="flex h-6 w-6 items-center justify-center rounded-full bg-blue-100 text-xs font-bold text-blue-700 dark:bg-blue-950 dark:text-blue-300">2</span>
                            <div>
                                <h3 class="font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Overlay Assets (Optional)') }}</h3>
                                <p class="text-xs text-zinc-500">{{ __('Optional: Add images or video clips to overlay, or leave empty to process/download the main video directly.') }}</p>
                            </div>
                        </div>

                        <!-- Add Asset Buttons -->
                        <div class="flex items-center gap-2">
                            <label class="cursor-pointer inline-flex items-center gap-1 rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white shadow-xs hover:bg-blue-700 transition-colors">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                <span>{{ __('Upload Asset') }}</span>
                                <input 
                                    type="file" 
                                    wire:model="newAssetFile" 
                                    class="hidden" 
                                    accept="video/mp4,video/webm,image/png,image/jpeg,image/webp,image/gif"
                                />
                            </label>
                        </div>
                    </div>

                    <!-- Library Video as Asset Modal/Dropdown (Secondary Option) -->
                    @if ($libraryVideos->count() > 0)
                        <div class="mb-4 flex items-center gap-2 rounded-lg bg-zinc-50 p-2.5 dark:bg-zinc-800/50 text-xs">
                            <span class="text-zinc-500 shrink-0">{{ __('Or add from library:') }}</span>
                            <div class="flex-1">
                                <select 
                                    wire:model.live="libraryAssetVideoId" 
                                    class="w-full rounded-md border border-zinc-300 bg-white px-2 py-1 text-xs dark:border-zinc-700 dark:bg-zinc-900 text-zinc-800 dark:text-zinc-200"
                                >
                                    <option value="">{{ __('-- Choose library video --') }}</option>
                                    @foreach ($libraryVideos as $lv)
                                        <option value="{{ $lv->id }}">{{ $lv->title ?: $lv->original_filename }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <button 
                                type="button" 
                                wire:click="addLibraryAsset" 
                                class="shrink-0 rounded bg-zinc-200 px-2.5 py-1 text-xs font-medium text-zinc-800 hover:bg-zinc-300 dark:bg-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-600 transition-colors"
                            >
                                {{ __('Add') }}
                            </button>
                        </div>
                    @endif

                    @error('assets') 
                        <div class="mb-4 rounded-lg bg-red-50 p-2.5 text-xs text-red-600 dark:bg-red-950/30 dark:text-red-400">
                            {{ $message }}
                        </div>
                    @enderror

                    <!-- Assets List -->
                    @if (count($assets) === 0)
                        <div class="rounded-lg border border-dashed border-zinc-300 p-8 text-center dark:border-zinc-700">
                            <svg class="mx-auto h-8 w-8 text-zinc-300 dark:text-zinc-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <p class="text-sm font-medium text-zinc-600 dark:text-zinc-400">{{ __('No overlay assets added (Optional).') }}</p>
                            <p class="text-xs text-zinc-400 mt-1">{{ __('You can proceed directly without overlays to save/download the main video, or click "Upload Asset" above.') }}</p>
                        </div>
                    @else
                        <div class="space-y-4">
                            @foreach ($assets as $index => $asset)
                                <div 
                                    class="rounded-xl border transition-all duration-150 p-4 {{ $activeAssetIndex === $index ? 'border-blue-500 bg-blue-50/20 dark:border-blue-500 dark:bg-blue-950/10 ring-1 ring-blue-500' : 'border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800/40' }}"
                                >
                                    <!-- Asset Header -->
                                    <div class="flex items-center justify-between gap-3 mb-3">
                                        <div class="flex items-center gap-2 min-w-0">
                                            <span class="flex h-5 w-5 items-center justify-center rounded bg-zinc-200 text-[11px] font-bold text-zinc-700 dark:bg-zinc-700 dark:text-zinc-300 shrink-0">
                                                #{{ $index + 1 }}
                                            </span>
                                            <span class="truncate text-xs font-semibold text-zinc-900 dark:text-zinc-100">
                                                {{ $asset['name'] }}
                                            </span>
                                            <span class="shrink-0 rounded px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wider {{ $asset['type'] === 'video' ? 'bg-purple-100 text-purple-700 dark:bg-purple-950/50 dark:text-purple-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-950/50 dark:text-amber-300' }}">
                                                {{ $asset['type'] }}
                                            </span>
                                            @if (!empty($asset['original_width']) && !empty($asset['original_height']))
                                                <span class="shrink-0 rounded bg-zinc-100 dark:bg-zinc-800 px-1.5 py-0.5 text-[10px] font-mono text-zinc-500" title="{{ __('Original media resolution') }}">
                                                    {{ $asset['original_width'] }}×{{ $asset['original_height'] }}
                                                </span>
                                            @endif
                                        </div>

                                        <div class="flex items-center gap-2">
                                            <button 
                                                type="button" 
                                                wire:click="selectAsset({{ $index }})" 
                                                class="text-xs text-blue-600 hover:underline dark:text-blue-400"
                                            >
                                                {{ $activeAssetIndex === $index ? __('Editing') : __('Select') }}
                                            </button>
                                            <button 
                                                type="button" 
                                                wire:click="removeAsset({{ $index }})" 
                                                class="text-red-500 hover:text-red-700 p-1 transition-colors" 
                                                title="{{ __('Delete Asset') }}"
                                            >
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Video-Specific Checklists: Green Screen, Audio & Aspect Ratio Lock -->
                                    @if ($asset['type'] === 'video')
                                        <div class="mb-3 grid grid-cols-1 sm:grid-cols-3 gap-3 rounded-lg bg-zinc-50 p-2.5 dark:bg-zinc-800/80">
                                            <!-- Green Screen Checklist -->
                                            <label class="flex items-center gap-2 cursor-pointer select-none">
                                                <input 
                                                    type="checkbox" 
                                                    wire:model.live="assets.{{ $index }}.remove_green_screen" 
                                                    class="h-4 w-4 rounded border-zinc-300 text-emerald-600 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-700"
                                                />
                                                <span class="text-xs font-medium text-zinc-700 dark:text-zinc-300">
                                                    {{ __('Remove Green Screen') }}
                                                </span>
                                                @if (!empty($asset['remove_green_screen']))
                                                    <span class="rounded bg-emerald-100 px-1 py-0.2 text-[9px] font-bold text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">CHROMA</span>
                                                @endif
                                            </label>

                                            <!-- Mute Audio Toggle -->
                                            <label class="flex items-center gap-2 cursor-pointer select-none">
                                                <input 
                                                    type="checkbox" 
                                                    wire:model.live="assets.{{ $index }}.mute_audio" 
                                                    class="h-4 w-4 rounded border-zinc-300 text-blue-600 focus:ring-blue-500 dark:border-zinc-600 dark:bg-zinc-700"
                                                />
                                                <span class="text-xs font-medium text-zinc-700 dark:text-zinc-300">
                                                    {{ __('Mute Overlay Audio') }}
                                                </span>
                                            </label>

                                            <!-- Lock Aspect Ratio Toggle -->
                                            <label class="flex items-center gap-2 cursor-pointer select-none">
                                                <input 
                                                    type="checkbox" 
                                                    wire:model.live="assets.{{ $index }}.lock_aspect_ratio" 
                                                    class="h-4 w-4 rounded border-zinc-300 text-indigo-600 focus:ring-indigo-500 dark:border-zinc-600 dark:bg-zinc-700"
                                                />
                                                <span class="text-xs font-medium text-zinc-700 dark:text-zinc-300">
                                                    {{ __('Lock Aspect Ratio') }}
                                                </span>
                                            </label>
                                        </div>
                                    @else
                                        <div class="mb-3 flex items-center gap-2 rounded-lg bg-zinc-50 p-2.5 dark:bg-zinc-800/80">
                                            <!-- Lock Aspect Ratio Toggle for Image -->
                                            <label class="flex items-center gap-2 cursor-pointer select-none">
                                                <input 
                                                    type="checkbox" 
                                                    wire:model.live="assets.{{ $index }}.lock_aspect_ratio" 
                                                    class="h-4 w-4 rounded border-zinc-300 text-indigo-600 focus:ring-indigo-500 dark:border-zinc-600 dark:bg-zinc-700"
                                                />
                                                <span class="text-xs font-medium text-zinc-700 dark:text-zinc-300">
                                                    {{ __('Lock Aspect Ratio') }}
                                                </span>
                                            </label>
                                        </div>
                                    @endif

                                    <!-- Position Presets & Coordinates Controls -->
                                    <div class="space-y-2 text-xs">
                                        <div class="flex items-center justify-between text-zinc-500">
                                            <span>{{ __('Position Presets:') }}</span>
                                            <div class="flex flex-wrap gap-1">
                                                <button type="button" wire:click="applyPositionPreset({{ $index }}, 'top-left')" class="rounded border border-zinc-200 px-1.5 py-0.5 text-[10px] hover:bg-zinc-100 dark:border-zinc-700 dark:hover:bg-zinc-800">{{ __('TL') }}</button>
                                                <button type="button" wire:click="applyPositionPreset({{ $index }}, 'top-center')" class="rounded border border-zinc-200 px-1.5 py-0.5 text-[10px] hover:bg-zinc-100 dark:border-zinc-700 dark:hover:bg-zinc-800">{{ __('TC') }}</button>
                                                <button type="button" wire:click="applyPositionPreset({{ $index }}, 'top-right')" class="rounded border border-zinc-200 px-1.5 py-0.5 text-[10px] hover:bg-zinc-100 dark:border-zinc-700 dark:hover:bg-zinc-800">{{ __('TR') }}</button>
                                                <button type="button" wire:click="applyPositionPreset({{ $index }}, 'center')" class="rounded border border-zinc-200 px-1.5 py-0.5 text-[10px] hover:bg-zinc-100 dark:border-zinc-700 dark:hover:bg-zinc-800">{{ __('Center') }}</button>
                                                <button type="button" wire:click="applyPositionPreset({{ $index }}, 'bottom-left')" class="rounded border border-zinc-200 px-1.5 py-0.5 text-[10px] hover:bg-zinc-100 dark:border-zinc-700 dark:hover:bg-zinc-800">{{ __('BL') }}</button>
                                                <button type="button" wire:click="applyPositionPreset({{ $index }}, 'bottom-center')" class="rounded border border-zinc-200 px-1.5 py-0.5 text-[10px] hover:bg-zinc-100 dark:border-zinc-700 dark:hover:bg-zinc-800">{{ __('BC') }}</button>
                                                <button type="button" wire:click="applyPositionPreset({{ $index }}, 'bottom-right')" class="rounded border border-zinc-200 px-1.5 py-0.5 text-[10px] hover:bg-zinc-100 dark:border-zinc-700 dark:hover:bg-zinc-800">{{ __('BR') }}</button>
                                            </div>
                                        </div>

                                        <!-- Coordinate Inputs (X, Y, W, H %) -->
                                        <div class="grid grid-cols-4 gap-2 pt-1">
                                            <div>
                                                <label class="text-[10px] text-zinc-400">X Position (%)</label>
                                                <input 
                                                    type="number" 
                                                    min="0" 
                                                    max="100" 
                                                    step="1"
                                                    wire:model.live.debounce.300ms="assets.{{ $index }}.x_percent" 
                                                    class="w-full rounded border border-zinc-200 px-2 py-1 text-xs dark:border-zinc-700 dark:bg-zinc-900"
                                                />
                                            </div>
                                            <div>
                                                <label class="text-[10px] text-zinc-400">Y Position (%)</label>
                                                <input 
                                                    type="number" 
                                                    min="0" 
                                                    max="100" 
                                                    step="1"
                                                    wire:model.live.debounce.300ms="assets.{{ $index }}.y_percent" 
                                                    class="w-full rounded border border-zinc-200 px-2 py-1 text-xs dark:border-zinc-700 dark:bg-zinc-900"
                                                />
                                            </div>
                                            <div>
                                                <label class="text-[10px] text-zinc-400">Width (%)</label>
                                                <input 
                                                    type="number" 
                                                    min="5" 
                                                    max="100" 
                                                    step="1"
                                                    wire:model.live.debounce.300ms="assets.{{ $index }}.width_percent" 
                                                    class="w-full rounded border border-zinc-200 px-2 py-1 text-xs dark:border-zinc-700 dark:bg-zinc-900"
                                                />
                                            </div>
                                            <div>
                                                <label class="text-[10px] text-zinc-400">Height (%)</label>
                                                <input 
                                                    type="number" 
                                                    min="5" 
                                                    max="100" 
                                                    step="1"
                                                    wire:model.live.debounce.300ms="assets.{{ $index }}.height_percent" 
                                                    class="w-full rounded border border-zinc-200 px-2 py-1 text-xs dark:border-zinc-700 dark:bg-zinc-900"
                                                />
                                            </div>
                                        </div>

                                        <!-- Optional Timestamps -->
                                        <div class="grid grid-cols-2 gap-2 pt-1 border-t border-zinc-100 dark:border-zinc-800">
                                            <div>
                                                <label class="text-[10px] text-zinc-400">{{ __('Start Second (Optional)') }}</label>
                                                <input 
                                                    type="number" 
                                                    min="0" 
                                                    step="0.1" 
                                                    placeholder="{{ __('Start (0s)') }}"
                                                    wire:model.live="assets.{{ $index }}.start_seconds" 
                                                    class="w-full rounded border border-zinc-200 px-2 py-1 text-xs dark:border-zinc-700 dark:bg-zinc-900"
                                                />
                                            </div>
                                            <div>
                                                <label class="text-[10px] text-zinc-400">{{ __('End Second (Optional)') }}</label>
                                                <input 
                                                    type="number" 
                                                    min="0" 
                                                    step="0.1" 
                                                    placeholder="{{ __('End (Full length)') }}"
                                                    wire:model.live="assets.{{ $index }}.end_seconds" 
                                                    class="w-full rounded border border-zinc-200 px-2 py-1 text-xs dark:border-zinc-700 dark:bg-zinc-900"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <!-- Submit Button & Library Option -->
                    <div class="mt-6 pt-4 border-t border-zinc-200 dark:border-zinc-700 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <label class="flex items-center gap-2 text-xs font-medium text-zinc-700 dark:text-zinc-300 cursor-pointer">
                            <input 
                                type="checkbox" 
                                wire:model.live="saveToLibrary" 
                                class="h-4 w-4 rounded border-zinc-300 text-blue-600 focus:ring-blue-500 dark:border-zinc-600 dark:bg-zinc-800"
                            />
                            <span>{{ __('Save rendered video to Video Library (reusable in future projects)') }}</span>
                        </label>

                        <flux:button 
                            variant="primary" 
                            type="button" 
                            wire:click="submitMerge"
                            wire:loading.attr="disabled"
                            wire:target="submitMerge, mainVideoFile, newAssetFile"
                        >
                            <span wire:loading.remove wire:target="submitMerge">
                                @if ($mainSourceType === 'url' && empty($assets))
                                    {{ __('⬇️ Download Video from URL') }}
                                @elseif (empty($assets))
                                    {{ __('🎬 Process Video') }}
                                @else
                                    {{ __('🎬 Merge & Render Video (:count)', ['count' => count($assets)]) }}
                                @endif
                            </span>
                            <span wire:loading wire:target="submitMerge" class="flex items-center gap-2">
                                <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                {{ __('Submitting...') }}
                            </span>
                        </flux:button>
                    </div>
                </div>
            </div>

            <!-- Right Column: Interactive Canvas Preview (5 cols) -->
            <div class="space-y-6 lg:col-span-5">
                <div class="sticky top-6 rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <span class="flex h-6 w-6 items-center justify-center rounded-full bg-blue-100 text-xs font-bold text-blue-700 dark:bg-blue-950 dark:text-blue-300">3</span>
                            <h3 class="font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Live Canvas Preview') }}</h3>
                        </div>
                        <span class="rounded bg-zinc-100 px-2 py-0.5 text-xs font-mono font-semibold text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                            {{ $aspectRatio === '16:9' ? '1920 × 1080 (16:9)' : '1080 × 1920 (9:16)' }}
                        </span>
                    </div>
                    <p class="text-xs text-zinc-500 mb-4">
                        {{ __('Drag the boxes inside the preview canvas to position assets, and drag the bottom-right corner to resize.') }}
                    </p>

                    <!-- Interactive Canvas Container with Alpine.js -->
                    <div 
                        x-data="{
                            aspectRatio: @entangle('aspectRatio'),
                            assets: @entangle('assets'),
                            activeIdx: @entangle('activeAssetIndex'),
                            draggingIdx: null,
                            resizingIdx: null,
                            startX: 0,
                            startY: 0,
                            initX: 0,
                            initY: 0,
                            initW: 0,
                            initH: 0,
                            canvasRect: null,

                            startDrag(idx, event) {
                                this.activeIdx = idx;
                                this.draggingIdx = idx;
                                this.resizingIdx = null;
                                this.startX = event.clientX;
                                this.startY = event.clientY;
                                this.initX = parseFloat(this.assets[idx].x_percent) || 0;
                                this.initY = parseFloat(this.assets[idx].y_percent) || 0;
                                this.canvasRect = this.$refs.canvas.getBoundingClientRect();
                                window.addEventListener('mousemove', this.onMouseMove);
                                window.addEventListener('mouseup', this.onMouseUp);
                            },

                            startResize(idx, event) {
                                event.stopPropagation();
                                this.activeIdx = idx;
                                this.resizingIdx = idx;
                                this.draggingIdx = null;
                                this.startX = event.clientX;
                                this.startY = event.clientY;
                                this.initW = parseFloat(this.assets[idx].width_percent) || 20;
                                this.initH = parseFloat(this.assets[idx].height_percent) || 20;
                                this.canvasRect = this.$refs.canvas.getBoundingClientRect();
                                window.addEventListener('mousemove', this.onMouseMove);
                                window.addEventListener('mouseup', this.onMouseUp);
                            },

                            onMouseMove(event) {
                                if (!$data.canvasRect) return;
                                const cw = $data.canvasRect.width;
                                const ch = $data.canvasRect.height;
                                if (cw <= 0 || ch <= 0) return;

                                if ($data.draggingIdx !== null) {
                                    const idx = $data.draggingIdx;
                                    const dxPercent = ((event.clientX - $data.startX) / cw) * 100;
                                    const dyPercent = ((event.clientY - $data.startY) / ch) * 100;
                                    const maxW = parseFloat($data.assets[idx].width_percent) || 10;
                                    const maxH = parseFloat($data.assets[idx].height_percent) || 10;

                                    let newX = Math.max(0, Math.min(100 - maxW, $data.initX + dxPercent));
                                    let newY = Math.max(0, Math.min(100 - maxH, $data.initY + dyPercent));
                                    $data.assets[idx].x_percent = Math.round(newX * 10) / 10;
                                    $data.assets[idx].y_percent = Math.round(newY * 10) / 10;
                                }

                                if ($data.resizingIdx !== null) {
                                    const idx = $data.resizingIdx;
                                    const dwPercent = ((event.clientX - $data.startX) / cw) * 100;
                                    const dhPercent = ((event.clientY - $data.startY) / ch) * 100;
                                    const curX = parseFloat($data.assets[idx].x_percent) || 0;
                                    const curY = parseFloat($data.assets[idx].y_percent) || 0;
                                    const isLocked = ($data.assets[idx].lock_aspect_ratio !== false);

                                    if (isLocked) {
                                        const canvasRatio = ($data.aspectRatio === '9:16') ? (9 / 16) : (16 / 9);
                                        // Lock strictly to the original asset's media aspect ratio (W / H)
                                        const origAspect = parseFloat($data.assets[idx].original_aspect_ratio) || 
                                            (($data.initH > 0) ? (($data.initW / $data.initH) * canvasRatio) : 1);

                                        // Ratio of height% to width%: Ph = Pw * (canvasRatio / origAspect)
                                        const ratioHtoW = (origAspect > 0) ? (canvasRatio / origAspect) : 1;
                                        const delta = (dwPercent + (dhPercent / (ratioHtoW || 1))) / 2;
                                        let newW = $data.initW + delta;

                                        const maxW = 100 - curX;
                                        const maxH = 100 - curY;

                                        newW = Math.max(5, Math.min(maxW, newW));
                                        let newH = newW * ratioHtoW;

                                        if (newH > maxH) {
                                            newH = maxH;
                                            newW = newH / ratioHtoW;
                                        }
                                        if (newH < 5) {
                                            newH = 5;
                                            newW = newH / ratioHtoW;
                                        }

                                        $data.assets[idx].width_percent = Math.round(newW * 10) / 10;
                                        $data.assets[idx].height_percent = Math.round(newH * 10) / 10;
                                    } else {
                                        let newW = Math.max(5, Math.min(100 - curX, $data.initW + dwPercent));
                                        let newH = Math.max(5, Math.min(100 - curY, $data.initH + dhPercent));
                                        $data.assets[idx].width_percent = Math.round(newW * 10) / 10;
                                        $data.assets[idx].height_percent = Math.round(newH * 10) / 10;
                                    }
                                }
                            },

                            onMouseUp() {
                                window.removeEventListener('mousemove', $data.onMouseMove);
                                window.removeEventListener('mouseup', $data.onMouseUp);
                                $data.draggingIdx = null;
                                $data.resizingIdx = null;
                            }
                        }"
                        class="flex flex-col items-center justify-center rounded-xl bg-zinc-950 p-4 shadow-inner"
                    >
                        <!-- Canvas Box with Strict Aspect Ratio -->
                        <div 
                            x-ref="canvas"
                            class="relative w-full max-w-full overflow-hidden rounded-lg bg-zinc-900 border border-zinc-800 shadow-2xl transition-all duration-200 select-none {{ $aspectRatio === '16:9' ? 'aspect-video max-h-[380px]' : 'aspect-[9/16] max-h-[500px] w-[280px]' }}"
                        >
                            <!-- Main Video Placeholder Background -->
                            <div class="absolute inset-0 flex flex-col items-center justify-center p-4 text-center text-zinc-600 dark:text-zinc-500">
                                <svg class="h-10 w-10 opacity-40 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span class="text-xs font-medium opacity-60">{{ __('Base Video Canvas') }}</span>
                                <span class="text-[10px] opacity-40">({{ $aspectRatio }})</span>
                                <span x-show="!assets || assets.length === 0" class="mt-2 rounded bg-zinc-800/80 px-2 py-0.5 text-[10px] text-zinc-400 font-mono">
                                    {{ __('Direct Mode: No overlays') }}
                                </span>
                            </div>

                            <!-- Overlay Asset Boxes -->
                            <template x-for="(asset, idx) in assets" :key="asset.id || idx">
                                <div 
                                    class="absolute cursor-move overflow-hidden rounded border transition-shadow duration-75 flex flex-col justify-between"
                                    :class="{
                                        'border-blue-400 bg-blue-500/20 ring-2 ring-blue-400 z-30 shadow-lg': activeIdx === idx,
                                        'border-white/70 bg-white/10 hover:border-blue-300 z-20': activeIdx !== idx
                                    }"
                                    :style="`left: ${asset.x_percent}%; top: ${asset.y_percent}%; width: ${asset.width_percent}%; height: ${asset.height_percent}%;`"
                                    @mousedown="startDrag(idx, $event)"
                                >
                                    <!-- Asset Tag/Badge -->
                                    <div class="flex items-center justify-between p-1 bg-black/60 text-[9px] text-white font-mono leading-none backdrop-blur-xs">
                                        <span class="truncate font-semibold" x-text="'#' + (idx + 1) + ' ' + (asset.name || '')"></span>
                                        <span 
                                            x-show="asset.remove_green_screen" 
                                            class="rounded bg-emerald-500 px-1 py-0.5 text-[8px] font-bold text-white uppercase shrink-0"
                                        >
                                            CHROMA
                                        </span>
                                    </div>

                                    <!-- Asset Media Preview Image / Icon inside box -->
                                    <div class="flex-1 flex items-center justify-center overflow-hidden p-1 pointer-events-none">
                                        <template x-if="asset.preview_url">
                                            <template x-if="asset.type === 'image'">
                                                <img :src="asset.preview_url" class="max-h-full max-w-full object-contain rounded" />
                                            </template>
                                        </template>
                                        <template x-if="!asset.preview_url || asset.type === 'video'">
                                            <div class="text-center text-white/80">
                                                <template x-if="asset.type === 'video'">
                                                    <svg class="h-6 w-6 mx-auto opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                                    </svg>
                                                </template>
                                                <template x-if="asset.type === 'image' && !asset.preview_url">
                                                    <svg class="h-6 w-6 mx-auto opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                    </svg>
                                                </template>
                                                <span class="text-[9px] block opacity-80" x-text="`${Math.round(asset.width_percent)}% × ${Math.round(asset.height_percent)}%`"></span>
                                            </div>
                                        </template>
                                    </div>

                                    <!-- Bottom-Right Resizer Handle -->
                                    <div 
                                        class="absolute bottom-0 right-0 h-4 w-4 cursor-se-resize bg-blue-500 flex items-center justify-center text-white rounded-tl shadow-md hover:scale-110 transition-transform"
                                        @mousedown="startResize(idx, $event)"
                                        title="{{ __('Drag to resize') }}"
                                    >
                                        <svg class="h-2.5 w-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 19L5 5m14 0v14H5" />
                                        </svg>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Projects & Rendered Videos History Section -->
        <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 mt-8">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <flux:heading size="lg" level="2">{{ __('Merged Video Projects') }}</flux:heading>
                    <flux:subheading>{{ __('Monitor background rendering progress and download your finalized videos.') }}</flux:subheading>
                </div>
                @if ($hasActiveJobs)
                    <div class="flex items-center gap-2 text-xs font-semibold text-blue-600 dark:text-blue-400">
                        <svg class="animate-spin h-3.5 w-3.5" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>{{ __('Live updates active...') }}</span>
                    </div>
                @endif
            </div>

            @if ($projects->isEmpty())
                <div class="rounded-lg border border-dashed border-zinc-200 p-8 text-center text-zinc-500 dark:border-zinc-700">
                    <p class="text-sm">{{ __('No merged video projects yet. Create your first merge above!') }}</p>
                </div>
            @else
                <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach ($projects as $proj)
                        <div class="flex flex-col gap-4 py-4 sm:flex-row sm:items-center sm:justify-between">
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <h4 class="font-semibold text-sm text-zinc-900 dark:text-zinc-100">{{ $proj->title }}</h4>
                                    <span class="rounded bg-zinc-100 px-1.5 py-0.5 text-[10px] font-mono text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400">
                                        {{ $proj->aspect_ratio }}
                                    </span>
                                </div>
                                <div class="flex flex-wrap items-center gap-3 text-xs text-zinc-500">
                                    <span>{{ count($proj->assets_config ?? []) }} {{ __('overlay assets') }}</span>
                                    <span>•</span>
                                    <span>{{ $proj->created_at->diffForHumans() }}</span>
                                    <span>•</span>
                                    <!-- Status Badge -->
                                    @if ($proj->status === 'completed')
                                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300">
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                            {{ __('Completed') }}
                                        </span>
                                    @elseif ($proj->status === 'rendering')
                                        <span class="inline-flex items-center gap-1 rounded-full bg-blue-50 px-2 py-0.5 text-[11px] font-semibold text-blue-700 dark:bg-blue-950/50 dark:text-blue-300">
                                            <svg class="animate-spin h-2.5 w-2.5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                            {{ __('Rendering video...') }}
                                        </span>
                                    @elseif ($proj->status === 'downloading_source')
                                        <span class="inline-flex items-center gap-1 rounded-full bg-purple-50 px-2 py-0.5 text-[11px] font-semibold text-purple-700 dark:bg-purple-950/50 dark:text-purple-300">
                                            {{ __('Downloading source...') }}
                                        </span>
                                    @elseif ($proj->status === 'failed')
                                        <span class="inline-flex items-center gap-1 rounded-full bg-red-50 px-2 py-0.5 text-[11px] font-semibold text-red-700 dark:bg-red-950/50 dark:text-red-300" title="{{ $proj->error_message }}">
                                            <span class="h-1.5 w-1.5 rounded-full bg-red-500"></span>
                                            {{ __('Failed') }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 rounded-full bg-zinc-100 px-2 py-0.5 text-[11px] font-semibold text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400">
                                            {{ __('Queued') }}
                                        </span>
                                    @endif
                                </div>
                                @if ($proj->error_message)
                                    <p class="text-[11px] text-red-600 dark:text-red-400 max-w-lg truncate">{{ $proj->error_message }}</p>
                                @endif
                            </div>

                            <div class="flex items-center gap-2">
                                @if ($proj->status === 'completed' && $proj->output_url)
                                    <!-- Video Library Badge or Save Button -->
                                    @if ($proj->saved_video_id)
                                        <span class="inline-flex items-center gap-1 rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-1.5 text-xs font-semibold text-emerald-700 dark:border-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300">
                                            <svg class="h-3.5 w-3.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                            {{ __('In Library') }}
                                        </span>
                                    @else
                                        <button 
                                            type="button" 
                                            wire:click="saveProjectToLibrary({{ $proj->id }})" 
                                            wire:loading.attr="disabled"
                                            class="inline-flex items-center gap-1 rounded-lg border border-zinc-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-zinc-700 hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700 transition-colors shadow-2xs"
                                            title="{{ __('Save to Video Library for future use') }}"
                                        >
                                            <svg class="h-3.5 w-3.5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 4H6a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-2m-4-1v8m0 0l3-3m-3 3L9 8m-5 5h2.586a1 1 0 01.707.293l2.414 2.414a1 1 0 00.707.293h3.172a1 1 0 00.707-.293l2.414-2.414a1 1 0 01.707-.293H20" />
                                            </svg>
                                            {{ __('+ Save to Library') }}
                                        </button>
                                    @endif

                                    <!-- Video Preview Modal / Player Trigger -->
                                    <a 
                                        href="{{ $proj->output_url }}" 
                                        target="_blank"
                                        class="inline-flex items-center gap-1 rounded-lg border border-zinc-200 bg-white px-3 py-1.5 text-xs font-semibold text-zinc-700 hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700 transition-colors shadow-2xs"
                                    >
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        {{ __('Preview') }}
                                    </a>

                                    <!-- Download Link -->
                                    <a 
                                        href="{{ $proj->output_url }}" 
                                        download
                                        class="inline-flex items-center gap-1 rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-blue-700 transition-colors shadow-2xs"
                                    >
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                        </svg>
                                        {{ __('Download') }}
                                    </a>
                                @endif

                                <button 
                                    type="button" 
                                    wire:click="deleteProject({{ $proj->id }})" 
                                    wire:confirm="{{ __('Are you sure you want to delete this project?') }}"
                                    class="text-zinc-400 hover:text-red-600 p-1.5 transition-colors"
                                    title="{{ __('Delete Project') }}"
                                >
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    @endforeach

                    <div class="pt-4">
                        {{ $projects->links() }}
                    </div>
                </div>
            @endif
        </div>
    @endif
</div>
