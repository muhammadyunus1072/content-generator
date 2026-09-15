<?php

namespace App\Services;

use App\Models\Episode;
use Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class ComicArchiveExportService
{
    /**
     * Export an Episode's manga pages into a standard CBZ (Comic Book Zip) package.
     *
     * @return string Relative path to generated .cbz file
     */
    public function exportCbzArchive(Episode $episode): string
    {
        $mangaPages = $this->ensureMangaPagesExist($episode);

        $slug = Str::slug($episode->title ?: 'episode_'.$episode->episode_number);
        $cbzRelative = "stories/manga_exports/episode_{$episode->id}/{$slug}_comic.cbz";
        $cbzAbs = Storage::disk('public')->path($cbzRelative);
        File::ensureDirectoryExists(dirname($cbzAbs));

        if (File::exists($cbzAbs)) {
            File::delete($cbzAbs);
        }

        $zip = new ZipArchive;
        if ($zip->open($cbzAbs, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new Exception("Failed to create CBZ archive at: {$cbzAbs}");
        }

        // 1. Add all manga pages in sequence (page_001.jpg, page_002.jpg, ...)
        $pageCount = count($mangaPages);
        foreach ($mangaPages as $idx => $p) {
            $pageNumber = $idx + 1;
            $paddedIdx = str_pad((string) $pageNumber, 3, '0', STR_PAD_LEFT);
            $entryName = "page_{$paddedIdx}.jpg";

            $sourceAbs = $this->resolveAbsolutePath($p['image_path']);
            if (File::exists($sourceAbs)) {
                $zip->addFile($sourceAbs, $entryName);
            }
        }

        // 2. Generate standard ComicInfo.xml metadata descriptor
        $comicInfoXml = $this->generateComicInfoXml($episode, $pageCount);
        $zip->addFromString('ComicInfo.xml', $comicInfoXml);

        $zip->close();

        $episode->update(['cbz_package_path' => $cbzRelative]);

        return $cbzRelative;
    }

    /**
     * Export an Episode's manga pages into a print/digital PDF document.
     *
     * @return string Relative path to generated .pdf file
     */
    public function exportPdfDocument(Episode $episode): string
    {
        $mangaPages = $this->ensureMangaPagesExist($episode);

        $slug = Str::slug($episode->title ?: 'episode_'.$episode->episode_number);
        $pdfRelative = "stories/manga_exports/episode_{$episode->id}/{$slug}_print.pdf";
        $pdfAbs = Storage::disk('public')->path($pdfRelative);
        File::ensureDirectoryExists(dirname($pdfAbs));

        // Build valid, pure-PHP PDF document embedding JPEG pages
        $pdfContent = $this->compilePdfFromImages($mangaPages, $episode->title ?: 'Manga Episode');
        File::put($pdfAbs, $pdfContent);

        $episode->update(['pdf_package_path' => $pdfRelative]);

        return $pdfRelative;
    }

    /**
     * Generate ComicInfo.xml metadata adhering to ComicRack / Apple Books standards.
     */
    public function generateComicInfoXml(Episode $episode, int $pageCount): string
    {
        $seriesTitle = htmlspecialchars($episode->story?->contentTitle?->title ?: ($episode->story?->title ?: 'Comic Series'), ENT_XML1);
        $epTitle = htmlspecialchars($episode->title ?: "Episode {$episode->episode_number}", ENT_XML1);
        $synopsis = htmlspecialchars($episode->synopsis ?: ($episode->narrative_script ?: ''), ENT_XML1);
        $genre = htmlspecialchars($episode->story?->contentTitle?->genre ?: ($episode->story?->genre ?: 'Manga'), ENT_XML1);
        $isRtl = ($episode->manga_reading_direction === 'rtl') ? 'YesAndRightToLeft' : 'No';

        return <<<XML
<?xml version="1.0" encoding="utf-8"?>
<ComicInfo xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
  <Title>{$epTitle}</Title>
  <Series>{$seriesTitle}</Series>
  <Number>{$episode->episode_number}</Number>
  <Summary>{$synopsis}</Summary>
  <Genre>{$genre}</Genre>
  <PageCount>{$pageCount}</PageCount>
  <Manga>{$isRtl}</Manga>
</ComicInfo>
XML;
    }

    /**
     * Compile a collection of JPEG image pages into a standards-compliant multi-page PDF document.
     *
     * @param  array<int, array{image_path: string}>  $mangaPages
     */
    protected function compilePdfFromImages(array $mangaPages, string $docTitle): string
    {
        $objects = [];
        $offsets = [];

        // Standard A4 page geometry at 72 DPI (595.28 x 841.89 points)
        $pageW = 595.28;
        $pageH = 841.89;

        $pageObjectIds = [];
        $nextObjId = 3; // 1 = Catalog, 2 = Pages root

        $imageDetails = [];

        foreach ($mangaPages as $p) {
            $sourceAbs = $this->resolveAbsolutePath($p['image_path']);
            if (! File::exists($sourceAbs)) {
                continue;
            }

            $imgData = File::get($sourceAbs);
            $info = @getimagesize($sourceAbs);
            if (! $info) {
                continue;
            }

            $w = $info[0];
            $h = $info[1];

            $imgObjId = $nextObjId++;
            $contentObjId = $nextObjId++;
            $pageObjId = $nextObjId++;

            $pageObjectIds[] = $pageObjId;

            $imageDetails[] = [
                'img_id' => $imgObjId,
                'content_id' => $contentObjId,
                'page_id' => $pageObjId,
                'width' => $w,
                'height' => $h,
                'data' => $imgData,
            ];
        }

        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";

        // Object 1: Catalog
        $offsets[1] = strlen($pdf);
        $pdf .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";

        // Object 2: Pages Root
        $kidsStr = implode(' 0 R ', $pageObjectIds).(count($pageObjectIds) ? ' 0 R' : '');
        $pageCount = count($pageObjectIds);
        $offsets[2] = strlen($pdf);
        $pdf .= "2 0 obj\n<< /Type /Pages /Kids [ {$kidsStr} ] /Count {$pageCount} >>\nendobj\n";

        // Generate Page, Content, and Image Objects
        foreach ($imageDetails as $item) {
            // Image XObject
            $dataLen = strlen($item['data']);
            $offsets[$item['img_id']] = strlen($pdf);
            $pdf .= "{$item['img_id']} 0 obj\n<< /Type /XObject /Subtype /Image /Width {$item['width']} /Height {$item['height']} /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length {$dataLen} >>\nstream\n".$item['data']."\nendstream\nendobj\n";

            // Content Stream: scale and position image on page
            $contentStream = "q {$pageW} 0 0 {$pageH} 0 0 cm /Im1 Do Q\n";
            $streamLen = strlen($contentStream);
            $offsets[$item['content_id']] = strlen($pdf);
            $pdf .= "{$item['content_id']} 0 obj\n<< /Length {$streamLen} >>\nstream\n{$contentStream}endstream\nendobj\n";

            // Page Object
            $offsets[$item['page_id']] = strlen($pdf);
            $pdf .= "{$item['page_id']} 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [ 0 0 {$pageW} {$pageH} ] /Contents {$item['content_id']} 0 R /Resources << /XObject << /Im1 {$item['img_id']} 0 R >> >> >>\nendobj\n";
        }

        // Cross-Reference Table
        $xrefOffset = strlen($pdf);
        $totalObjs = $nextObjId;
        $pdf .= "xref\n0 {$totalObjs}\n0000000000 65535 f \n";

        for ($i = 1; $i < $totalObjs; $i++) {
            $offset = $offsets[$i] ?? 0;
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        // Trailer
        $pdf .= "trailer\n<< /Size {$totalObjs} /Root 1 0 R >>\nstartxref\n{$xrefOffset}\n%%EOF";

        return $pdf;
    }

    protected function ensureMangaPagesExist(Episode $episode): array
    {
        if (! empty($episode->manga_pages) && is_array($episode->manga_pages)) {
            return $episode->manga_pages;
        }

        $layoutService = app(MangaPageLayoutService::class);

        return $layoutService->generateMangaPages($episode);
    }

    protected function resolveAbsolutePath(string $path): string
    {
        if (File::exists($path)) {
            return $path;
        }

        return Storage::disk('public')->path($path);
    }
}
