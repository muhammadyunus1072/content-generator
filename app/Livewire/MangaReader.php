<?php

namespace App\Livewire;

use App\Jobs\GenerateMangaPagesJob;
use App\Models\Episode;
use App\Services\ComicArchiveExportService;
use App\Services\MangaPageLayoutService;
use Flux\Flux;
use Livewire\Component;

class MangaReader extends Component
{
    public Episode $episode;

    public int $currentPage = 1; // 1-based page number

    public string $viewMode = 'spread'; // 'spread' (dual-page) or 'single'

    public string $fitMode = 'fit-height'; // 'fit-height' or 'fit-width'

    public string $readingDirection = 'rtl'; // 'rtl' (Manga) or 'ltr' (Comic)

    public bool $showDrawer = false;

    public function mount(Episode $episode): void
    {
        $this->episode = $episode->load(['story.contentTitle', 'boards']);
        $this->readingDirection = $episode->manga_reading_direction ?: 'rtl';

        // If not yet generated, attempt instant generation
        if (empty($this->episode->manga_pages) && $this->episode->boards->isNotEmpty()) {
            try {
                $service = app(MangaPageLayoutService::class);
                $service->generateMangaPages($this->episode, $this->readingDirection);
                $this->episode->refresh();
            } catch (\Exception $e) {
                // Background job can also generate
            }
        }
    }

    public function setPage(int $pageNumber): void
    {
        $totalPages = count($this->episode->manga_pages ?? []);
        if ($totalPages === 0) {
            return;
        }

        $this->currentPage = max(1, min($totalPages, $pageNumber));
    }

    public function nextPage(): void
    {
        $totalPages = count($this->episode->manga_pages ?? []);
        if ($this->viewMode === 'spread' && $this->currentPage > 1) {
            $this->setPage($this->currentPage + 2);
        } else {
            $this->setPage($this->currentPage + 1);
        }
    }

    public function previousPage(): void
    {
        if ($this->viewMode === 'spread' && $this->currentPage > 2) {
            $this->setPage($this->currentPage - 2);
        } else {
            $this->setPage($this->currentPage - 1);
        }
    }

    public function toggleViewMode(): void
    {
        $this->viewMode = ($this->viewMode === 'spread') ? 'single' : 'spread';
    }

    public function toggleDirection(): void
    {
        $this->readingDirection = ($this->readingDirection === 'rtl') ? 'ltr' : 'rtl';
        $this->episode->update(['manga_reading_direction' => $this->readingDirection]);
    }

    public function toggleFitMode(): void
    {
        $this->fitMode = ($this->fitMode === 'fit-height') ? 'fit-width' : 'fit-height';
    }

    public function regeneratePages(): void
    {
        GenerateMangaPagesJob::dispatch($this->episode, $this->readingDirection);
        Flux::toast(variant: 'success', text: __('Manga pages regeneration queued!'));
    }

    public function downloadCbz(ComicArchiveExportService $exporter)
    {
        try {
            $cbzRelative = $exporter->exportCbzArchive($this->episode);
            $this->episode->refresh();
            Flux::toast(variant: 'success', text: __('CBZ Comic Archive ready!'));

            return response()->download(storage_path("app/public/{$cbzRelative}"));
        } catch (\Exception $e) {
            Flux::toast(variant: 'danger', text: __('Export failed: ').$e->getMessage());
        }
    }

    public function downloadPdf(ComicArchiveExportService $exporter)
    {
        try {
            $pdfRelative = $exporter->exportPdfDocument($this->episode);
            $this->episode->refresh();
            Flux::toast(variant: 'success', text: __('Print PDF Document ready!'));

            return response()->download(storage_path("app/public/{$pdfRelative}"));
        } catch (\Exception $e) {
            Flux::toast(variant: 'danger', text: __('Export failed: ').$e->getMessage());
        }
    }

    public function nextEpisode()
    {
        $next = Episode::where('story_id', $this->episode->story_id)
            ->where('episode_number', '>', $this->episode->episode_number)
            ->orderBy('episode_number')
            ->first();

        if ($next) {
            return redirect()->route('manga-reader', $next->id);
        }
    }

    public function previousEpisode()
    {
        $prev = Episode::where('story_id', $this->episode->story_id)
            ->where('episode_number', '<', $this->episode->episode_number)
            ->orderByDesc('episode_number')
            ->first();

        if ($prev) {
            return redirect()->route('manga-reader', $prev->id);
        }
    }

    public function render()
    {
        $story = $this->episode->story;
        $mangaPages = $this->episode->manga_pages ?? [];
        $totalPages = count($mangaPages);

        $leftPage = null;
        $rightPage = null;

        if ($this->viewMode === 'single' || $this->currentPage === 1 || $totalPages <= 1) {
            // Single page or standalone Cover page
            $pageIndex = max(0, $this->currentPage - 1);
            $rightPage = $mangaPages[$pageIndex] ?? null;
        } else {
            // Dual-page spread
            $firstSpreadIdx = $this->currentPage - 1;
            $secondSpreadIdx = $firstSpreadIdx + 1;

            $p1 = $mangaPages[$firstSpreadIdx] ?? null;
            $p2 = $mangaPages[$secondSpreadIdx] ?? null;

            if ($this->readingDirection === 'rtl') {
                // In Japanese Manga RTL:
                // Right page is read first (lower index), Left page is read second (higher index)
                $rightPage = $p1;
                $leftPage = $p2;
            } else {
                // In Western Comic LTR:
                // Left page is read first, Right page is read second
                $leftPage = $p1;
                $rightPage = $p2;
            }
        }

        $prevEpisode = Episode::where('story_id', $this->episode->story_id)
            ->where('episode_number', '<', $this->episode->episode_number)
            ->orderByDesc('episode_number')
            ->first();

        $nextEpisode = Episode::where('story_id', $this->episode->story_id)
            ->where('episode_number', '>', $this->episode->episode_number)
            ->orderBy('episode_number')
            ->first();

        return view('livewire.manga-reader', [
            'story' => $story,
            'mangaPages' => $mangaPages,
            'totalPages' => $totalPages,
            'leftPage' => $leftPage,
            'rightPage' => $rightPage,
            'prevEpisode' => $prevEpisode,
            'nextEpisode' => $nextEpisode,
        ]);
    }
}
