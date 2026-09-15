<div wire:poll.5s class="w-full">
    <div class="relative overflow-hidden rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex items-center justify-between mb-6">
            <div>
                <flux:heading size="lg" level="2" class="mb-1">{{ __('My Videos') }}</flux:heading>
                <flux:subheading>{{ __('Monitor the progress of your video-to-audio extraction and content generation.') }}</flux:subheading>
            </div>
            <!-- Dynamic count badge -->
            <span class="inline-flex items-center rounded-full bg-zinc-100 px-2.5 py-0.5 text-xs font-medium text-zinc-850 dark:bg-zinc-855 dark:text-zinc-300">
                {{ trans_choice('{0} No videos|{1} 1 Video|[2,*] :count Videos', $videos->count(), ['count' => $videos->count()]) }}
            </span>
        </div>

        @if ($videos->isEmpty())
            <div class="flex flex-col items-center justify-center py-12 text-center">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-zinc-50 text-zinc-400 dark:bg-zinc-800 dark:text-zinc-500">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                </div>
                <h3 class="mt-4 text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ __('No videos uploaded yet') }}</h3>
                <p class="mt-1 text-sm text-zinc-550 dark:text-zinc-400">{{ __('Upload a video above to begin processing.') }}</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-zinc-500 dark:text-zinc-400">
                    <thead class="bg-zinc-50 text-xs font-semibold uppercase text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                        <tr>
                            <th scope="col" class="px-6 py-3 rounded-l-lg">{{ __('Video Details') }}</th>
                            <th scope="col" class="px-6 py-3">{{ __('Status') }}</th>
                            <th scope="col" class="px-6 py-3">{{ __('Audio Link') }}</th>
                            <th scope="col" class="px-6 py-3 rounded-r-lg">{{ __('Uploaded') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                        @foreach ($videos as $video)
                            <tr class="group hover:bg-zinc-50/50 dark:hover:bg-zinc-800/20 transition-colors duration-150 cursor-pointer {{ $selectedVideoId === $video->id ? 'bg-zinc-50/50 dark:bg-zinc-800/30' : '' }}" wire:click="selectVideo({{ $video->id }})">
                                <!-- Details -->
                                <td class="px-6 py-4 font-medium text-zinc-900 dark:text-zinc-100">
                                    <div class="flex flex-col">
                                        <span class="truncate max-w-xs font-semibold">{{ $video->title ?: pathinfo($video->original_filename, PATHINFO_FILENAME) }}</span>
                                        <span class="text-xs text-zinc-500 dark:text-zinc-400 truncate max-w-xs mt-0.5">{{ $video->original_filename }}</span>
                                    </div>
                                </td>

                                <!-- Status -->
                                <td class="px-6 py-4">
                                    <div class="flex flex-col">
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            <span class="inline-flex w-fit items-center rounded-full border px-2.5 py-0.5 text-xs font-medium {{ $this->getStatusBadgeClass($video->status) }}">
                                                <span class="mr-1.5 h-2 w-2 rounded-full {{ 
                                                    $video->status === 'failed' ? 'bg-red-500 animate-pulse' : (
                                                    $video->status === 'completed' || $video->status === 'audio_extracted' ? 'bg-emerald-500' : 'bg-blue-500 animate-pulse'
                                                ) }}"></span>
                                                {{ $this->getStatusLabel($video->status) }}
                                            </span>
                                            @if (($video->processing_mode ?? '') === 'manual')
                                                <span class="inline-flex items-center rounded-full bg-zinc-100 dark:bg-zinc-800 text-[10px] font-medium px-2 py-0.5 text-zinc-600 dark:text-zinc-400 border border-zinc-200 dark:border-zinc-700">
                                                    {{ __('Manual') }}
                                                </span>
                                            @endif
                                        </div>
                                        @if ($video->status === 'failed' && $video->error_message)
                                            <span class="mt-1 text-[11px] text-red-500 dark:text-red-400 max-w-xs truncate" title="{{ $video->error_message }}">
                                                {{ $video->error_message }}
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                <!-- Audio Link -->
                                <td class="px-6 py-4 text-xs font-medium">
                                    @if ($video->audio_path)
                                        <a href="{{ Storage::url($video->audio_path) }}" target="_blank" wire:click.stop class="inline-flex items-center gap-1.5 text-blue-600 dark:text-blue-400 hover:underline">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3" />
                                            </svg>
                                            {{ __('Listen Audio') }}
                                        </a>
                                    @else
                                        <span class="text-zinc-400 dark:text-zinc-600">-</span>
                                    @endif
                                </td>

                                <!-- Uploaded time -->
                                <td class="px-6 py-4 text-xs text-zinc-500 dark:text-zinc-400 whitespace-nowrap">
                                    {{ $video->created_at->diffForHumans() }}
                                </td>
                            </tr>


                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
