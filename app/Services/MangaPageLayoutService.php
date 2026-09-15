<?php

namespace App\Services;

use App\Models\Episode;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class MangaPageLayoutService
{
    public const PAGE_WIDTH = 1600;

    public const PAGE_HEIGHT = 2400;

    public const BLEED_MARGIN = 48;

    public const GUTTER_SPACING = 24;

    public const BORDER_WIDTH = 5;

    /**
     * Generate structured manga pages from an episode's scene boards.
     *
     * @return array<int, array{page_number: int, layout: string, image_path: string, panel_board_ids: array<int>}>
     */
    public function generateMangaPages(Episode $episode, ?string $direction = null): array
    {
        $readingDirection = $direction ?: ($episode->manga_reading_direction ?: 'rtl');
        $episode->update([
            'manga_status' => 'generating',
            'manga_reading_direction' => $readingDirection,
        ]);

        $boards = $episode->boards()
            ->where(function ($q) {
                $q->whereNotNull('speech_bubble_image_path')
                    ->orWhereNotNull('image_path');
            })
            ->orderBy('sequence_order')
            ->get();

        if ($boards->isEmpty()) {
            throw new Exception('Cannot generate manga pages: Episode has no ready panel images.');
        }

        try {
            // 1. Partition scene boards into page clusters (1-5 panels per page)
            $pageClusters = $this->clusterBoardsIntoPages($boards);

            $generatedPages = [];
            $pageNumber = 1;
            $outputDir = "stories/manga_pages/episode_{$episode->id}";

            foreach ($pageClusters as $cluster) {
                $layoutType = $cluster['layout'];
                $clusterBoards = $cluster['boards'];

                // 2. Render composite page canvas
                $pageRelativePath = "{$outputDir}/page_".str_pad((string) $pageNumber, 3, '0', STR_PAD_LEFT).'.jpg';
                $pageAbsPath = Storage::disk('public')->path($pageRelativePath);
                File::ensureDirectoryExists(dirname($pageAbsPath));

                $this->renderPageCanvas(
                    $clusterBoards,
                    $layoutType,
                    $readingDirection,
                    $pageAbsPath
                );

                $generatedPages[] = [
                    'page_number' => $pageNumber,
                    'layout' => $layoutType,
                    'image_path' => $pageRelativePath,
                    'panel_board_ids' => $clusterBoards->pluck('id')->toArray(),
                ];

                $pageNumber++;
            }

            $episode->update([
                'manga_pages' => $generatedPages,
                'manga_status' => 'completed',
            ]);

            return $generatedPages;
        } catch (Exception $e) {
            $episode->update(['manga_status' => 'failed']);
            Log::error('MangaPageLayoutService failed', [
                'episode_id' => $episode->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Group boards into pages respecting hints (splash, wide, 4-koma).
     *
     * @param  Collection  $boards
     * @return array<int, array{layout: string, boards: Collection}>
     */
    public function clusterBoardsIntoPages($boards): array
    {
        $clusters = [];
        $remaining = $boards->values();
        $total = $remaining->count();
        $i = 0;

        while ($i < $total) {
            $currentBoard = $remaining[$i];

            // 1. Explicit full splash hint
            if ($currentBoard->page_layout_hint === 'splash') {
                $clusters[] = [
                    'layout' => 'splash',
                    'boards' => $remaining->slice($i, 1),
                ];
                $i += 1;

                continue;
            }

            // 2. Determine batch size based on remaining count and hints
            $leftover = $total - $i;

            if ($leftover === 1) {
                $clusters[] = [
                    'layout' => 'splash',
                    'boards' => $remaining->slice($i, 1),
                ];
                $i += 1;
            } elseif ($leftover === 2) {
                $clusters[] = [
                    'layout' => 'split_2_horizontal',
                    'boards' => $remaining->slice($i, 2),
                ];
                $i += 2;
            } elseif ($leftover === 3) {
                $clusters[] = [
                    'layout' => 'layout_3_top_wide',
                    'boards' => $remaining->slice($i, 3),
                ];
                $i += 3;
            } elseif ($leftover === 4) {
                $clusters[] = [
                    'layout' => 'grid_4',
                    'boards' => $remaining->slice($i, 4),
                ];
                $i += 4;
            } else {
                // Take 4 or 5 panels for standard manga pacing
                $take = ($leftover % 4 === 0) ? 4 : 5;
                if ($take === 5 && $leftover >= 5) {
                    $clusters[] = [
                        'layout' => 'dynamic_5',
                        'boards' => $remaining->slice($i, 5),
                    ];
                    $i += 5;
                } else {
                    $clusters[] = [
                        'layout' => 'grid_4',
                        'boards' => $remaining->slice($i, 4),
                    ];
                    $i += 4;
                }
            }
        }

        return $clusters;
    }

    /**
     * Render the composite manga page on disk using PHP GD.
     *
     * @param  Collection  $boards
     */
    protected function renderPageCanvas($boards, string $layout, string $direction, string $outputAbsPath): void
    {
        $page = imagecreatetruecolor(self::PAGE_WIDTH, self::PAGE_HEIGHT);

        // Fill background with traditional paper white (slightly warm)
        $paperBg = imagecolorallocate($page, 253, 253, 253);
        imagefilledrectangle($page, 0, 0, self::PAGE_WIDTH, self::PAGE_HEIGHT, $paperBg);

        // Get panel bounding boxes based on layout geometry
        $cells = $this->calculateCellGeometry($layout, $direction, count($boards));

        $boardArray = $boards->values();
        $boardCount = count($boardArray);

        for ($idx = 0; $idx < min($boardCount, count($cells)); $idx++) {
            $board = $boardArray[$idx];
            $cell = $cells[$idx];

            $imgPath = $board->speech_bubble_image_path ?: $board->image_path;
            $absPath = $this->resolveAbsolutePath($imgPath);

            if (File::exists($absPath)) {
                $this->drawPanelIntoCell($page, $absPath, $cell);
            } else {
                // Blank dark cell if image missing
                $cellBg = imagecolorallocate($page, 30, 30, 35);
                imagefilledrectangle($page, $cell['x'], $cell['y'], $cell['x'] + $cell['w'], $cell['y'] + $cell['h'], $cellBg);
            }

            // Draw crisp manga frame border
            $this->drawCellBorder($page, $cell);
        }

        // Save page as high quality JPEG
        imagejpeg($page, $outputAbsPath, 92);
        imagedestroy($page);
    }

    /**
     * Compute cell geometry coordinates for each layout type.
     *
     * @return array<int, array{x: int, y: int, w: int, h: int}>
     */
    public function calculateCellGeometry(string $layout, string $direction, int $panelCount = 4): array
    {
        $usableW = self::PAGE_WIDTH - (self::BLEED_MARGIN * 2);
        $usableH = self::PAGE_HEIGHT - (self::BLEED_MARGIN * 2);
        $startX = self::BLEED_MARGIN;
        $startY = self::BLEED_MARGIN;
        $gutter = self::GUTTER_SPACING;

        $isRtl = ($direction === 'rtl');

        return match ($layout) {
            'splash' => [
                ['x' => $startX, 'y' => $startY, 'w' => $usableW, 'h' => $usableH],
            ],

            'split_2_horizontal' => [
                ['x' => $startX, 'y' => $startY, 'w' => $usableW, 'h' => (int) (($usableH - $gutter) / 2)],
                ['x' => $startX, 'y' => $startY + (int) (($usableH - $gutter) / 2) + $gutter, 'w' => $usableW, 'h' => (int) (($usableH - $gutter) / 2)],
            ],

            'layout_3_top_wide' => [
                // Top wide panel
                ['x' => $startX, 'y' => $startY, 'w' => $usableW, 'h' => (int) (($usableH - $gutter) * 0.45)],
                // Bottom 2 panels: In RTL, Panel 1 is on the Right!
                $isRtl
                    ? ['x' => $startX + (int) (($usableW - $gutter) / 2) + $gutter, 'y' => $startY + (int) (($usableH - $gutter) * 0.45) + $gutter, 'w' => (int) (($usableW - $gutter) / 2), 'h' => (int) (($usableH - $gutter) * 0.55)]
                    : ['x' => $startX, 'y' => $startY + (int) (($usableH - $gutter) * 0.45) + $gutter, 'w' => (int) (($usableW - $gutter) / 2), 'h' => (int) (($usableH - $gutter) * 0.55)],
                $isRtl
                    ? ['x' => $startX, 'y' => $startY + (int) (($usableH - $gutter) * 0.45) + $gutter, 'w' => (int) (($usableW - $gutter) / 2), 'h' => (int) (($usableH - $gutter) * 0.55)]
                    : ['x' => $startX + (int) (($usableW - $gutter) / 2) + $gutter, 'y' => $startY + (int) (($usableH - $gutter) * 0.45) + $gutter, 'w' => (int) (($usableW - $gutter) / 2), 'h' => (int) (($usableH - $gutter) * 0.55)],
            ],

            'grid_4' => [
                // 2x2 grid: Top Row
                $isRtl
                    ? ['x' => $startX + (int) (($usableW - $gutter) / 2) + $gutter, 'y' => $startY, 'w' => (int) (($usableW - $gutter) / 2), 'h' => (int) (($usableH - $gutter) / 2)]
                    : ['x' => $startX, 'y' => $startY, 'w' => (int) (($usableW - $gutter) / 2), 'h' => (int) (($usableH - $gutter) / 2)],
                $isRtl
                    ? ['x' => $startX, 'y' => $startY, 'w' => (int) (($usableW - $gutter) / 2), 'h' => (int) (($usableH - $gutter) / 2)]
                    : ['x' => $startX + (int) (($usableW - $gutter) / 2) + $gutter, 'y' => $startY, 'w' => (int) (($usableW - $gutter) / 2), 'h' => (int) (($usableH - $gutter) / 2)],
                // Bottom Row
                $isRtl
                    ? ['x' => $startX + (int) (($usableW - $gutter) / 2) + $gutter, 'y' => $startY + (int) (($usableH - $gutter) / 2) + $gutter, 'w' => (int) (($usableW - $gutter) / 2), 'h' => (int) (($usableH - $gutter) / 2)]
                    : ['x' => $startX, 'y' => $startY + (int) (($usableH - $gutter) / 2) + $gutter, 'w' => (int) (($usableW - $gutter) / 2), 'h' => (int) (($usableH - $gutter) / 2)],
                $isRtl
                    ? ['x' => $startX, 'y' => $startY + (int) (($usableH - $gutter) / 2) + $gutter, 'w' => (int) (($usableW - $gutter) / 2), 'h' => (int) (($usableH - $gutter) / 2)]
                    : ['x' => $startX + (int) (($usableW - $gutter) / 2) + $gutter, 'y' => $startY + (int) (($usableH - $gutter) / 2) + $gutter, 'w' => (int) (($usableW - $gutter) / 2), 'h' => (int) (($usableH - $gutter) / 2)],
            ],

            'dynamic_5' => [
                // 1 Top Wide Panel
                ['x' => $startX, 'y' => $startY, 'w' => $usableW, 'h' => (int) ($usableH * 0.35)],
                // Middle 2 Split Panels
                $isRtl
                    ? ['x' => $startX + (int) (($usableW - $gutter) / 2) + $gutter, 'y' => $startY + (int) ($usableH * 0.35) + $gutter, 'w' => (int) (($usableW - $gutter) / 2), 'h' => (int) ($usableH * 0.30)]
                    : ['x' => $startX, 'y' => $startY + (int) ($usableH * 0.35) + $gutter, 'w' => (int) (($usableW - $gutter) / 2), 'h' => (int) ($usableH * 0.30)],
                $isRtl
                    ? ['x' => $startX, 'y' => $startY + (int) ($usableH * 0.35) + $gutter, 'w' => (int) (($usableW - $gutter) / 2), 'h' => (int) ($usableH * 0.30)]
                    : ['x' => $startX + (int) (($usableW - $gutter) / 2) + $gutter, 'y' => $startY + (int) ($usableH * 0.35) + $gutter, 'w' => (int) (($usableW - $gutter) / 2), 'h' => (int) ($usableH * 0.30)],
                // Bottom 2 Split Panels
                $isRtl
                    ? ['x' => $startX + (int) (($usableW - $gutter) / 2) + $gutter, 'y' => $startY + (int) ($usableH * 0.65) + ($gutter * 2), 'w' => (int) (($usableW - $gutter) / 2), 'h' => (int) ($usableH * 0.35) - ($gutter * 2)]
                    : ['x' => $startX, 'y' => $startY + (int) ($usableH * 0.65) + ($gutter * 2), 'w' => (int) (($usableW - $gutter) / 2), 'h' => (int) ($usableH * 0.35) - ($gutter * 2)],
                $isRtl
                    ? ['x' => $startX, 'y' => $startY + (int) ($usableH * 0.65) + ($gutter * 2), 'w' => (int) (($usableW - $gutter) / 2), 'h' => (int) ($usableH * 0.35) - ($gutter * 2)]
                    : ['x' => $startX + (int) (($usableW - $gutter) / 2) + $gutter, 'y' => $startY + (int) ($usableH * 0.65) + ($gutter * 2), 'w' => (int) (($usableW - $gutter) / 2), 'h' => (int) ($usableH * 0.35) - ($gutter * 2)],
            ],

            default => [
                ['x' => $startX, 'y' => $startY, 'w' => $usableW, 'h' => $usableH],
            ],
        };
    }

    /**
     * Draw and center-crop panel image into target cell.
     *
     * @param  resource|\GdImage  $page
     * @param  array{x: int, y: int, w: int, h: int}  $cell
     */
    protected function drawPanelIntoCell($page, string $imagePath, array $cell): void
    {
        $info = @getimagesize($imagePath);
        if (! $info) {
            return;
        }

        $srcW = $info[0];
        $srcH = $info[1];
        $src = match ($info[2]) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($imagePath),
            IMAGETYPE_PNG => imagecreatefrompng($imagePath),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? imagecreatefromwebp($imagePath) : null,
            default => null,
        };

        if (! $src) {
            return;
        }

        // Calculate aspect ratio crop to fill cell completely
        $targetRatio = $cell['w'] / $cell['h'];
        $srcRatio = $srcW / $srcH;

        if ($srcRatio > $targetRatio) {
            // Source is wider than cell -> crop sides
            $cropW = (int) round($srcH * $targetRatio);
            $cropH = $srcH;
            $cropX = (int) round(($srcW - $cropW) / 2);
            $cropY = 0;
        } else {
            // Source is taller than cell -> crop top/bottom
            $cropW = $srcW;
            $cropH = (int) round($srcW / $targetRatio);
            $cropX = 0;
            $cropY = (int) round(($srcH - $cropH) / 2);
        }

        imagecopyresampled(
            $page,
            $src,
            $cell['x'],
            $cell['y'],
            $cropX,
            $cropY,
            $cell['w'],
            $cell['h'],
            $cropW,
            $cropH
        );

        imagedestroy($src);
    }

    /**
     * Draw solid black manga frame border around cell.
     *
     * @param  resource|\GdImage  $page
     * @param  array{x: int, y: int, w: int, h: int}  $cell
     */
    protected function drawCellBorder($page, array $cell): void
    {
        $borderCol = imagecolorallocate($page, 15, 15, 15);
        $thickness = self::BORDER_WIDTH;

        for ($t = 0; $t < $thickness; $t++) {
            imagerectangle(
                $page,
                $cell['x'] + $t,
                $cell['y'] + $t,
                $cell['x'] + $cell['w'] - $t - 1,
                $cell['y'] + $cell['h'] - $t - 1,
                $borderCol
            );
        }
    }

    protected function resolveAbsolutePath(string $path): string
    {
        if (File::exists($path)) {
            return $path;
        }

        return Storage::disk('public')->path($path);
    }
}
