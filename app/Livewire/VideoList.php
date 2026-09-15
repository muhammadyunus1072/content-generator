<?php

namespace App\Livewire;

use App\Models\Video;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class VideoList extends Component
{
    /**
     * Currently selected video ID.
     */
    public ?int $selectedVideoId = null;

    /**
     * Listen for the video-uploaded event to refresh the video list.
     */
    #[On('video-uploaded')]
    public function refresh(): void
    {
        // Livewire automatically triggers render() when a component's state or event is fired.
    }

    /**
     * Select or deselect a video and dispatch the selection event.
     */
    public function selectVideo(int $videoId): void
    {
        if ($this->selectedVideoId === $videoId) {
            $this->selectedVideoId = null;
        } else {
            $this->selectedVideoId = $videoId;
        }

        $this->dispatch('video-selected', videoId: $videoId);
    }

    /**
     * Get the status color classes for styling the badge.
     */
    public function getStatusBadgeClass(string $status): string
    {
        return match ($status) {
            'downloading' => 'bg-orange-50 text-orange-800 dark:bg-orange-950/30 dark:text-orange-300 border-orange-200 dark:border-orange-900',
            'pending' => 'bg-zinc-100 text-zinc-800 dark:bg-zinc-800 dark:text-zinc-300 border-zinc-200 dark:border-zinc-700',
            'extracting_audio' => 'bg-amber-50 text-amber-800 dark:bg-amber-950/30 dark:text-amber-300 border-amber-200 dark:border-amber-900',
            'audio_extracted' => 'bg-blue-50 text-blue-800 dark:bg-blue-950/30 dark:text-blue-300 border-blue-200 dark:border-blue-900',
            'transcribing' => 'bg-indigo-50 text-indigo-800 dark:bg-indigo-950/30 dark:text-indigo-300 border-indigo-200 dark:border-indigo-900',
            'transcribed' => 'bg-violet-50 text-violet-800 dark:bg-violet-950/30 dark:text-violet-300 border-violet-200 dark:border-violet-900',
            'ai_processing' => 'bg-cyan-50 text-cyan-800 dark:bg-cyan-950/30 dark:text-cyan-300 border-cyan-200 dark:border-cyan-900',
            'completed' => 'bg-emerald-50 text-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-300 border-emerald-200 dark:border-emerald-900',
            'failed' => 'bg-red-50 text-red-800 dark:bg-red-950/30 dark:text-red-300 border-red-200 dark:border-red-900',
            default => 'bg-zinc-50 text-zinc-800 dark:bg-zinc-800 dark:text-zinc-300 border-zinc-200 dark:border-zinc-700',
        };
    }

    /**
     * Get human-readable status labels.
     */
    public function getStatusLabel(string $status): string
    {
        return match ($status) {
            'downloading' => __('Downloading Video'),
            'pending' => __('Pending'),
            'extracting_audio' => __('Extracting Audio'),
            'audio_extracted' => __('Audio Extracted'),
            'transcribing' => __('Transcribing Speech'),
            'transcribed' => __('Transcribed'),
            'ai_processing' => __('AI Content Analysis'),
            'completed' => __('Completed'),
            'failed' => __('Failed'),
            default => ucfirst(str_replace('_', ' ', $status)),
        };
    }

    /**
     * Render the component view.
     */
    public function render()
    {
        $videos = Video::query()
            ->where('user_id', Auth::id())
            ->latest()
            ->get();

        return view('livewire.video-list', [
            'videos' => $videos,
        ]);
    }
}
