<?php

namespace App\Services;

use App\Models\Episode;
use Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class WebtoonStripService
{
    /**
     * Standard Webtoon Canvas width in pixels.
     */
    public const WEBTOON_WIDTH = 800;

    /**
     * Maximum height per slice for Webtoon Canvas.
     */
    public const WEBTOON_MAX_SLICE_HEIGHT = 1280;

    /**
     * Generate a continuous vertical webtoon strip and automatically slice it for publishing.
     *
     * @return array{strip_path: string, slices: array<int, string>}
     */
    public function generateAndSliceEpisode(Episode $episode, int $targetWidth = self::WEBTOON_WIDTH): array
    {
        $boards = $episode->boards()
            ->where(function ($q) {
                $q->whereNotNull('speech_bubble_image_path')
                    ->orWhereNotNull('image_path');
            })
            ->orderBy('sequence_order')
            ->get();

        if ($boards->isEmpty()) {
            throw new Exception('Cannot generate webtoon: Episode has no ready panel images.');
        }

        $episode->update(['webtoon_status' => 'generating']);

        try {
            // 1. Calculate dimensions and load source panels
            $panelData = [];
            $totalHeight = 0;

            foreach ($boards as $board) {
                $imgPath = $board->speech_bubble_image_path ?: $board->image_path;
                $absPath = $this->resolveAbsolutePath($imgPath);

                if (! File::exists($absPath)) {
                    continue;
                }

                $info = @getimagesize($absPath);
                if (! $info) {
                    continue;
                }

                $origW = $info[0];
                $origH = $info[1];
                $scaleRatio = $targetWidth / $origW;
                $scaledH = (int) round($origH * $scaleRatio);
                $gutter = (int) ($board->gutter_spacing ?? 80);

                $panelData[] = [
                    'path' => $absPath,
                    'type' => $info[2],
                    'orig_w' => $origW,
                    'orig_h' => $origH,
                    'scaled_h' => $scaledH,
                    'gutter' => $gutter,
                    'y_start' => $totalHeight,
                    'y_end' => $totalHeight + $scaledH,
                ];

                $totalHeight += $scaledH + $gutter;
            }

            if (empty($panelData)) {
                throw new Exception('No valid images could be loaded for webtoon strip.');
            }

            // 2. Stitch panels into continuous vertical canvas
            $stripImage = imagecreatetruecolor($targetWidth, $totalHeight);
            $bgColor = imagecolorallocate($stripImage, 15, 15, 20); // Dark sleek canvas
            imagefilledrectangle($stripImage, 0, 0, $targetWidth, $totalHeight, $bgColor);

            $currentY = 0;
            foreach ($panelData as $p) {
                $src = $this->loadGdImage($p['path'], $p['type']);
                if ($src) {
                    imagecopyresampled(
                        $stripImage,
                        $src,
                        0,
                        $currentY,
                        0,
                        0,
                        $targetWidth,
                        $p['scaled_h'],
                        $p['orig_w'],
                        $p['orig_h']
                    );
                    imagedestroy($src);
                }
                $currentY += $p['scaled_h'] + $p['gutter'];
            }

            // Save full continuous strip
            $stripRelative = 'stories/webtoons/episode_'.$episode->id.'_full_strip.webp';
            $stripAbs = Storage::disk('public')->path($stripRelative);
            File::ensureDirectoryExists(dirname($stripAbs));

            if (function_exists('imagewebp')) {
                imagewebp($stripImage, $stripAbs, 90);
            } else {
                imagejpeg($stripImage, $stripAbs, 90);
            }

            // 3. Smart Seam Slicing into platform-compliant images (max 1280px)
            $slices = $this->smartSliceStrip(
                $stripImage,
                $targetWidth,
                $totalHeight,
                $panelData,
                $episode->id
            );

            imagedestroy($stripImage);

            $episode->update([
                'webtoon_strip_path' => $stripRelative,
                'webtoon_slices' => $slices,
                'webtoon_status' => 'completed',
            ]);

            return [
                'strip_path' => $stripRelative,
                'slices' => $slices,
            ];
        } catch (Exception $e) {
            $episode->update(['webtoon_status' => 'failed']);
            Log::error('WebtoonStripService failed', [
                'episode_id' => $episode->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Smart Slicing Algorithm:
     * Slices the continuous strip into segments up to maxSliceHeight,
     * ensuring slicing cut lines fall in empty inter-panel gutters instead of cutting through character faces or dialogue.
     *
     * @param  array<int, array{y_start: int, y_end: int, gutter: int}>  $panelData
     * @return array<int, string> Relative paths of slice images
     */
    protected function smartSliceStrip($stripImage, int $width, int $totalHeight, array $panelData, int $episodeId): array
    {
        $slices = [];
        $sliceMaxH = self::WEBTOON_MAX_SLICE_HEIGHT;
        $currentY = 0;
        $sliceIndex = 1;

        $tempSliceDir = "stories/webtoons/slices/episode_{$episodeId}";

        while ($currentY < $totalHeight) {
            $idealCutY = $currentY + $sliceMaxH;

            if ($idealCutY >= $totalHeight) {
                // Last segment
                $actualCutY = $totalHeight;
            } else {
                // Find nearest gutter boundary at or before idealCutY (must not exceed max slice height)
                $bestCut = null;
                $minDist = 999999;

                foreach ($panelData as $p) {
                    $gutterY = $p['y_end'] + (int) ($p['gutter'] / 2);
                    if ($gutterY > $currentY && $gutterY <= $idealCutY) {
                        $dist = abs($gutterY - $idealCutY);
                        if ($dist < $minDist) {
                            $minDist = $dist;
                            $bestCut = $gutterY;
                        }
                    }
                }

                $actualCutY = $bestCut ?: $idealCutY;
            }

            $sliceHeight = $actualCutY - $currentY;
            if ($sliceHeight <= 0) {
                break;
            }

            $sliceCanvas = imagecreatetruecolor($width, $sliceHeight);
            imagecopy(
                $sliceCanvas,
                $stripImage,
                0,
                0,
                0,
                $currentY,
                $width,
                $sliceHeight
            );

            $paddedIdx = str_pad((string) $sliceIndex, 3, '0', STR_PAD_LEFT);
            $sliceRelative = "{$tempSliceDir}/slice_{$paddedIdx}.jpg";
            $sliceAbs = Storage::disk('public')->path($sliceRelative);
            File::ensureDirectoryExists(dirname($sliceAbs));

            // Webtoon Canvas compliant JPEG (high quality, small filesize <2MB)
            imagejpeg($sliceCanvas, $sliceAbs, 92);
            imagedestroy($sliceCanvas);

            $slices[] = $sliceRelative;
            $currentY = $actualCutY;
            $sliceIndex++;
        }

        return $slices;
    }

    protected function loadGdImage(string $path, int $type)
    {
        return match ($type) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($path),
            IMAGETYPE_PNG => imagecreatefrompng($path),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? imagecreatefromwebp($path) : null,
            default => null,
        };
    }

    protected function resolveAbsolutePath(string $path): string
    {
        if (File::exists($path)) {
            return $path;
        }

        return Storage::disk('public')->path($path);
    }
}
