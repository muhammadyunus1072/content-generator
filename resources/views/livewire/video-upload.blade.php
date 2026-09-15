<div class="w-full">
    <div class="relative overflow-hidden rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="lg" level="2" class="mb-1">{{ __('Upload Video') }}</flux:heading>
        <flux:subheading class="mb-6">{{ __('Upload your podcast or video file (MP4, MOV, WebM, etc.) up to 500MB.') }}</flux:subheading>

        <!-- Tab Switcher -->
        <div class="flex gap-2 mb-6">
            <button 
                type="button" 
                wire:click="$set('mode', 'file')" 
                class="flex-1 py-2 px-4 rounded-lg text-sm font-medium border transition-colors duration-150 {{ $mode === 'file' ? 'bg-zinc-900 text-white border-zinc-900 dark:bg-zinc-100 dark:text-zinc-900 dark:border-zinc-100' : 'bg-transparent text-zinc-600 border-zinc-200 hover:bg-zinc-50 dark:text-zinc-400 dark:border-zinc-700 dark:hover:bg-zinc-800/50' }}"
            >
                {{ __('Upload File') }}
            </button>
            <button 
                type="button" 
                wire:click="$set('mode', 'url')" 
                class="flex-1 py-2 px-4 rounded-lg text-sm font-medium border transition-colors duration-150 {{ $mode === 'url' ? 'bg-zinc-900 text-white border-zinc-900 dark:bg-zinc-100 dark:text-zinc-900 dark:border-zinc-100' : 'bg-transparent text-zinc-600 border-zinc-200 hover:bg-zinc-50 dark:text-zinc-400 dark:border-zinc-700 dark:hover:bg-zinc-800/50' }}"
            >
                {{ __('Video URL (YouTube/etc.)') }}
            </button>
        </div>

        <form wire:submit="save" class="space-y-6">
            <!-- Custom Title Input -->
            <div>
                <flux:input 
                    wire:model="title" 
                    :label="__('Video Title (Optional)')" 
                    placeholder="{{ __('Enter a title for this video or leave blank to use automatic title') }}" 
                />
                @error('title')
                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            @if ($mode === 'file')
                <!-- Drag & Drop Zone -->
                <div 
                    x-data="{ 
                        uploading: false, 
                        progress: 0, 
                        isDragOver: false 
                    }"
                    x-on:livewire-upload-start="uploading = true"
                    x-on:livewire-upload-finish="uploading = false; progress = 0"
                    x-on:livewire-upload-error="uploading = false; progress = 0"
                    x-on:livewire-upload-progress="progress = $event.detail.progress"
                    class="relative"
                >
                    <div 
                        class="group relative flex cursor-pointer flex-col items-center justify-center rounded-lg border-2 border-dashed p-8 text-center transition-all duration-200"
                        :class="{
                            'border-zinc-300 bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800/50': !isDragOver && !uploading,
                            'border-blue-500 bg-blue-50/50 dark:border-blue-400 dark:bg-blue-950/20': isDragOver,
                            'border-zinc-200 bg-zinc-50/50 dark:border-zinc-800/20 pointer-events-none': uploading
                        }"
                        x-on:dragover.prevent="isDragOver = true"
                        x-on:dragleave.prevent="isDragOver = false"
                        x-on:drop="isDragOver = false"
                    >
                        <!-- File input element hidden but triggerable -->
                        <input 
                            type="file" 
                            wire:model="videoFile" 
                            id="videoFile" 
                            class="absolute inset-0 z-50 h-full w-full cursor-pointer opacity-0"
                            accept="video/mp4,video/quicktime,video/x-msvideo,video/webm,video/x-matroska"
                        />

                        <!-- Icons and labels -->
                        <div class="space-y-3" x-show="!uploading">
                            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400 transition-transform duration-200 group-hover:scale-110">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                </svg>
                            </div>
                            <div class="flex flex-col text-sm text-zinc-600 dark:text-zinc-300">
                                <span class="font-semibold text-blue-600 dark:text-blue-400 hover:underline">{{ __('Click to upload') }}</span>
                                <span class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __('or drag and drop video file here') }}</span>
                            </div>
                        </div>

                        <!-- Uploading state inside the dropzone -->
                        <div class="w-full space-y-3" x-show="uploading" x-cloak>
                            <div class="flex items-center justify-between text-xs font-medium text-zinc-500 dark:text-zinc-400">
                                <span class="flex items-center gap-2">
                                    <svg class="animate-spin h-4 w-4 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    {{ __('Uploading video file...') }}
                                </span>
                                <span x-text="progress + '%'"></span>
                            </div>
                            <div class="h-2 w-full overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
                                <div class="h-full bg-blue-600 dark:bg-blue-500 transition-all duration-150 ease-out" :style="'width: ' + progress + '%'"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Show selected file details once uploaded to temporary storage -->
                    @if ($videoFile && !$errors->has('videoFile'))
                        <div class="mt-3 flex items-center justify-between rounded-lg border border-emerald-200 bg-emerald-50/30 px-3 py-2 text-xs text-emerald-800 dark:border-emerald-950 dark:bg-emerald-950/20 dark:text-emerald-300">
                            <span class="flex items-center gap-2 font-medium truncate">
                                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span class="truncate">{{ $videoFile->getClientOriginalName() }}</span>
                            </span>
                            <span class="shrink-0 text-zinc-500">
                                ({{ number_format($videoFile->getSize() / 1024 / 1024, 2) }} MB)
                            </span>
                        </div>
                    @endif

                    @error('videoFile')
                        <p class="mt-2 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            @else
                <!-- Video URL Input -->
                <div>
                    <flux:input 
                        wire:model="videoUrl" 
                        :label="__('Video URL')" 
                        placeholder="{{ __('Paste YouTube, Vimeo, or other video link here') }}" 
                    />
                    @error('videoUrl')
                        <p class="mt-2 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            @endif

            <!-- Clipping Mode Selection -->
            <div class="space-y-2">
                <flux:select wire:model.live="clipMode" :label="__('Clipping Mode')">
                    <flux:select.option value="manual">{{ __('Manual (Direct Timestamp Clipping)') }}</flux:select.option>
                    <flux:select.option value="automatic">{{ __('Automatic (AI Extraction & Viral Analysis)') }}</flux:select.option>
                </flux:select>
                <p class="text-xs text-zinc-500 dark:text-zinc-400">
                    @if ($clipMode === 'manual')
                        {{ __('Bypasses AI audio extraction, transcription, and subtitles — clips are cut directly using your specified timestamps.') }}
                    @else
                        {{ __('Extracts audio, transcribes with Whisper, and uses AI to identify highlights and viral moments.') }}
                    @endif
                </p>
            </div>

            <!-- Manual Clip Timestamps Section -->
            @if ($clipMode === 'manual')
                <div class="space-y-3 rounded-xl border border-zinc-200/80 bg-zinc-50/70 p-4 dark:border-zinc-800 dark:bg-zinc-800/40">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="text-xs font-semibold uppercase tracking-wider text-zinc-700 dark:text-zinc-300">
                                {{ __('Manual Clip Timestamps') }}
                            </h4>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                {{ __('Add start and end times (in seconds) for each clip.') }}
                            </p>
                        </div>
                        <flux:button 
                            size="xs" 
                            variant="subtle" 
                            wire:click="addManualClip" 
                            type="button"
                        >
                            + {{ __('Add Clip') }}
                        </flux:button>
                    </div>

                    @error('manualClips')
                        <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror

                    <div class="space-y-2">
                        @foreach ($manualClips as $index => $clip)
                            <div class="flex items-center gap-2.5 bg-white p-2.5 rounded-lg border border-zinc-200 dark:border-zinc-700 dark:bg-zinc-900 shadow-2xs">
                                <span class="flex items-center justify-center h-7 w-7 rounded bg-zinc-100 dark:bg-zinc-800 text-xs font-semibold text-zinc-600 dark:text-zinc-300 shrink-0">
                                    #{{ $index + 1 }}
                                </span>
                                <div class="grid grid-cols-2 gap-2 flex-1">
                                    <div>
                                        <flux:input 
                                            type="number" 
                                            step="0.1" 
                                            min="0"
                                            wire:model="manualClips.{{ $index }}.start" 
                                            placeholder="{{ __('Start (sec)') }}"
                                        />
                                        @error("manualClips.{$index}.start")
                                            <p class="mt-0.5 text-[10px] text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div>
                                        <flux:input 
                                            type="number" 
                                            step="0.1" 
                                            min="0"
                                            wire:model="manualClips.{{ $index }}.end" 
                                            placeholder="{{ __('End (sec)') }}"
                                        />
                                        @error("manualClips.{$index}.end")
                                            <p class="mt-0.5 text-[10px] text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                                <flux:button 
                                    size="sm" 
                                    variant="ghost" 
                                    wire:click="removeManualClip({{ $index }})" 
                                    type="button"
                                    class="text-red-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-950/30 shrink-0"
                                    title="{{ __('Delete row') }}"
                                >
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </flux:button>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Submit Buttons -->
            <div class="flex items-center justify-end gap-3">
                <flux:button 
                    variant="primary" 
                    type="submit" 
                    wire:loading.attr="disabled"
                    wire:target="save, videoFile"
                    class="relative"
                >
                    <span wire:loading.remove wire:target="save">
                        {{ $clipMode === 'manual' ? __('Process & Cut Clips') : __('Upload & Process') }}
                    </span>
                    <span wire:loading wire:target="save" class="flex items-center gap-2">
                        <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        {{ __('Saving...') }}
                    </span>
                </flux:button>
            </div>
        </form>
    </div>
</div>
