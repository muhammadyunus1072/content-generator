<?php

namespace App\Jobs;

use App\Models\Episode;
use App\Services\ThumbnailGeneratorService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class GenerateThumbnailAssetsJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 300;

    public function __construct(
        public Episode $episode,
        public string $overlayText = ''
    ) {}

    public function handle(ThumbnailGeneratorService $thumbnailGenerator): void
    {
        // Find best panel/board to use as source (first with image)
        $board = $this->episode->boards()
            ->where(function ($q) {
                $q->whereNotNull('speech_bubble_image_path')
                    ->orWhereNotNull('image_path');
            })
            ->first();

        if (! $board) {
            return;
        }

        $sourceImage = $board->speech_bubble_image_path ?: $board->image_path;
        $text = $this->overlayText ?: ($this->episode->title ?: 'EPIC EPISODE');

        try {
            // Generate YouTube 16:9 thumbnail
            $ytThumb = $thumbnailGenerator->generate(
                $sourceImage,
                $text,
                'manhwa_recap',
                '16:9'
            );

            // Generate TikTok 9:16 cover
            $tiktokThumb = $thumbnailGenerator->generate(
                $sourceImage,
                $text,
                'manhwa_recap',
                '9:16'
            );

            Log::info('Generated multi-platform thumbnail assets for episode', [
                'episode_id' => $this->episode->id,
                'youtube_thumbnail' => $ytThumb,
                'tiktok_thumbnail' => $tiktokThumb,
            ]);
        } catch (Exception $e) {
            Log::error('GenerateThumbnailAssetsJob failed', [
                'episode_id' => $this->episode->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
