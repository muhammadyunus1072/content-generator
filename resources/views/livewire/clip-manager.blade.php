<div class="w-full" @if ($video && (! in_array($video->status, ['completed', 'failed']) || $video->clips->contains('status', 'processing') || $video->clips->contains('status', 'pending'))) wire:poll.3s @endif>
    @if ($video)
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-6 shadow-sm space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-zinc-150 dark:border-zinc-850">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-blue-500/10 text-blue-600 dark:text-blue-400 rounded-lg">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-lg text-zinc-900 dark:text-zinc-100">
                            {{ ($video->processing_mode ?? '') === 'manual' ? __('Manual clips for:') : __('AI suggested clips for:') }} <span class="text-blue-600 dark:text-blue-400">{{ $video->title ?: pathinfo($video->original_filename, PATHINFO_FILENAME) }}</span>
                        </h3>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                            {{ ($video->processing_mode ?? '') === 'manual' ? __('Review and regenerate your manually cut clips.') : __('Tweak suggested times and click "Cut Clip" to process the clip manually in background.') }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- List of Suggested Clips -->
            @if ($video->clips->isEmpty())
                <div class="rounded-xl border border-dashed border-zinc-200 dark:border-zinc-800 p-8 text-center text-zinc-500 dark:text-zinc-400">
                    <svg class="mx-auto h-10 w-10 text-zinc-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="text-sm font-semibold">
                        {{ ($video->processing_mode ?? '') === 'manual' ? __('No manual clips were added for this video.') : ($video->status === 'completed' ? __('No clip moments were suggested by Gemini.') : __('Suggested clips will show up once transcription and AI analysis is complete.')) }}
                    </p>
                </div>
            @else
                <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
                    @foreach ($video->clips as $clip)
                        <div class="flex flex-col bg-zinc-50/50 dark:bg-zinc-800/10 border border-zinc-200/60 dark:border-zinc-800/50 rounded-xl p-5 shadow-sm space-y-4 hover:border-zinc-300 dark:hover:border-zinc-700 transition-all duration-200">
                            <!-- Inputs Row -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
                                <div class="md:col-span-3">
                                    <flux:input 
                                        wire:model="editingClips.{{ $clip->id }}.title" 
                                        label="{{ __('Clip Title') }}"
                                    />
                                </div>
                                <div class="md:col-span-1">
                                    <flux:input 
                                        type="number" 
                                        step="0.01" 
                                        wire:model="editingClips.{{ $clip->id }}.start_seconds" 
                                        label="{{ __('Start (seconds)') }}"
                                    />
                                </div>
                                <div class="md:col-span-1">
                                    <flux:input 
                                        type="number" 
                                        step="0.01" 
                                        wire:model="editingClips.{{ $clip->id }}.end_seconds" 
                                        label="{{ __('End (seconds)') }}"
                                    />
                                </div>
                                <div class="md:col-span-1 text-xs text-zinc-500 font-medium pb-2 flex justify-between items-center">
                                    <span>{{ __('Duration:') }}</span>
                                    <span class="font-bold text-zinc-800 dark:text-zinc-200">
                                        {{ number_format(max(0, (float)($editingClips[$clip->id]['end_seconds'] ?? $clip->end_seconds) - (float)($editingClips[$clip->id]['start_seconds'] ?? $clip->start_seconds)), 2) }}s
                                    </span>
                                </div>
                            </div>

                            <!-- Actions & Status Line -->
                            <div class="flex items-center justify-between text-xs pt-3 border-t border-zinc-100 dark:border-zinc-850">
                                <div class="flex items-center gap-2">
                                    <span class="text-zinc-500">{{ __('Status:') }}</span>
                                    <span class="capitalize px-2.5 py-0.5 rounded-full text-[10px] font-semibold {{ 
                                        $clip->status === 'completed' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-400 border border-emerald-200/50' : (
                                        $clip->status === 'processing' ? 'bg-amber-50 text-amber-700 dark:bg-amber-950/30 dark:text-amber-400 animate-pulse border border-amber-200/50' : (
                                        $clip->status === 'failed' ? 'bg-red-50 text-red-700 dark:bg-red-955/30 dark:text-red-400 border border-red-200/50' : 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-400 border border-zinc-200/50'
                                    )) }}">
                                        {{ $clip->status }}
                                    </span>
                                </div>

                                <div class="flex items-center gap-3">
                                    @if ($clip->status === 'completed' && $clip->clip_path)
                                        <a href="{{ Storage::url($clip->clip_path) }}" target="_blank" class="text-blue-600 dark:text-blue-400 hover:underline inline-flex items-center gap-1 font-semibold">
                                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                            </svg>
                                            {{ __('Download') }}
                                        </a>
                                    @endif

                                    <flux:button 
                                        size="sm" 
                                        variant="filled" 
                                        wire:click="generateClip({{ $clip->id }})"
                                        wire:loading.attr="disabled"
                                    >
                                        {{ $clip->status === 'completed' ? __('Regenerate') : __('Cut Clip') }}
                                    </flux:button>
                                </div>
                            </div>

                            <!-- Embedded video player for completed clip -->
                            @if ($clip->status === 'completed' && $clip->clip_path)
                                <div class="aspect-video w-full overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-800 bg-black shadow-inner">
                                    <video src="{{ Storage::url($clip->clip_path) }}" controls class="h-full w-full"></video>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endif
</div>
