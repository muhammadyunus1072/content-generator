<?php

namespace App\Livewire;

use App\Jobs\GenerateWebtoonStripJob;
use App\Models\Episode;
use App\Services\WebtoonExportService;
use App\Services\WebtoonStripService;
use Flux\Flux;
use Livewire\Component;

class WebtoonReader extends Component
{
    public Episode $episode;

    public int $readerWidth = 800; // 600, 800, 1000

    public string $bgTheme = 'dark'; // 'dark' or 'light'

    public function mount(Episode $episode): void
    {
        $this->episode = $episode->load(['story.contentTitle', 'boards']);

        // If not yet generated, auto-generate strip
        if (empty($this->episode->webtoon_slices) && $this->episode->boards->isNotEmpty()) {
            try {
                $service = app(WebtoonStripService::class);
                $service->generateAndSliceEpisode($this->episode);
                $this->episode->refresh();
            } catch (\Exception $e) {
                // Slices will be generated on demand
            }
        }
    }

    public function setReaderWidth(int $width): void
    {
        $this->readerWidth = in_array($width, [600, 800, 1000]) ? $width : 800;
    }

    public function toggleBgTheme(): void
    {
        $this->bgTheme = $this->bgTheme === 'dark' ? 'light' : 'dark';
    }

    public function regenerateStrip(): void
    {
        GenerateWebtoonStripJob::dispatch($this->episode);
        Flux::toast(variant: 'success', text: __('Webtoon strip re-generation queued!'));
    }

    public function nextEpisode()
    {
        $next = Episode::where('story_id', $this->episode->story_id)
            ->where('episode_number', '>', $this->episode->episode_number)
            ->orderBy('episode_number')
            ->first();

        if ($next) {
            return redirect()->route('webtoon-reader', $next->id);
        }
    }

    public function previousEpisode()
    {
        $prev = Episode::where('story_id', $this->episode->story_id)
            ->where('episode_number', '<', $this->episode->episode_number)
            ->orderByDesc('episode_number')
            ->first();

        if ($prev) {
            return redirect()->route('webtoon-reader', $prev->id);
        }
    }

    public function downloadPublishingPack(WebtoonExportService $exporter)
    {
        try {
            $zipRelative = $exporter->exportPublishingPack($this->episode);
            $this->episode->refresh();
            Flux::toast(variant: 'success', text: __('Publishing pack ready for download!'));

            return response()->download(storage_path("app/public/{$zipRelative}"));
        } catch (\Exception $e) {
            Flux::toast(variant: 'danger', text: __('Export failed: ').$e->getMessage());
        }
    }

    public function render()
    {
        $story = $this->episode->story;
        $prevEpisode = Episode::where('story_id', $this->episode->story_id)
            ->where('episode_number', '<', $this->episode->episode_number)
            ->orderByDesc('episode_number')
            ->first();

        $nextEpisode = Episode::where('story_id', $this->episode->story_id)
            ->where('episode_number', '>', $this->episode->episode_number)
            ->orderBy('episode_number')
            ->first();

        return view('livewire.webtoon-reader', [
            'story' => $story,
            'prevEpisode' => $prevEpisode,
            'nextEpisode' => $nextEpisode,
        ]);
    }
}
