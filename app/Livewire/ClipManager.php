<?php

namespace App\Livewire;

use App\Jobs\CutClipJob;
use App\Models\Clip;
use App\Models\Video;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Component;

class ClipManager extends Component
{
    /**
     * Selected video ID.
     */
    public ?int $videoId = null;

    /**
     * Active timestamp values for editing suggested clips.
     *
     * @var array<int, array{title: string, start_seconds: float, end_seconds: float}>
     */
    public array $editingClips = [];

    /**
     * Listen for the video-selected event to load clips.
     */
    #[On('video-selected')]
    public function loadVideo(int $videoId): void
    {
        if ($this->videoId === $videoId) {
            $this->videoId = null;
            $this->editingClips = [];

            return;
        }

        $this->videoId = $videoId;
        $this->editingClips = [];
        $this->refreshClips();
    }

    /**
     * Refresh clip data from database.
     */
    public function refreshClips(): void
    {
        if (! $this->videoId) {
            return;
        }

        $video = Video::with('clips')->find($this->videoId);
        if (! $video) {
            $this->videoId = null;
            $this->editingClips = [];

            return;
        }

        // Initialize edit states for clips if not already present
        foreach ($video->clips as $clip) {
            if (! isset($this->editingClips[$clip->id])) {
                $this->editingClips[$clip->id] = [
                    'title' => $clip->title,
                    'start_seconds' => (float) $clip->start_seconds,
                    'end_seconds' => (float) $clip->end_seconds,
                ];
            }
        }
    }

    /**
     * Manually trigger the background clip generation job.
     */
    public function generateClip(int $clipId): void
    {
        $clip = Clip::findOrFail($clipId);
        $data = $this->editingClips[$clipId] ?? [];

        $start = (float) ($data['start_seconds'] ?? $clip->start_seconds);
        $end = (float) ($data['end_seconds'] ?? $clip->end_seconds);
        $title = (string) ($data['title'] ?? $clip->title);

        if ($start < 0 || $end <= $start) {
            Flux::toast(variant: 'danger', text: __('Invalid clip time. End seconds must be greater than start.'));

            return;
        }

        $clip->update([
            'title' => $title,
            'start_seconds' => $start,
            'end_seconds' => $end,
            'status' => 'processing',
        ]);

        // Dispatch background cutting job
        CutClipJob::dispatch($clip);

        Flux::toast(variant: 'success', text: __('Background cutting job started!'));
    }

    /**
     * Render the component view.
     */
    public function render()
    {
        $video = $this->videoId ? Video::with('clips')->find($this->videoId) : null;

        if ($video) {
            $this->refreshClips();
        }

        return view('livewire.clip-manager', [
            'video' => $video,
        ]);
    }
}
