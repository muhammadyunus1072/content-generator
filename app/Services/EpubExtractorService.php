<?php

namespace App\Services;

use DOMDocument;
use DOMElement;
use DOMXPath;
use Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class EpubExtractorService
{
    /**
     * Extract an EPUB file into structured scene pairs (text paragraphs + images).
     *
     * @return array<int, array{sequence_order: int, image_path: string|null, script_text: string}>
     */
    public function extractScenesFromEpub(string $epubAbsolutePath, int $projectId): array
    {
        if (! File::exists($epubAbsolutePath)) {
            throw new Exception("EPUB file does not exist at: {$epubAbsolutePath}");
        }

        $zip = new ZipArchive;
        $openResult = $zip->open($epubAbsolutePath);

        if ($openResult !== true) {
            throw new Exception("Failed to open EPUB as zip archive (Error code: {$openResult})");
        }

        $tempExtractDir = storage_path("app/temp/epub_extract_{$projectId}_".uniqid());
        File::ensureDirectoryExists($tempExtractDir);

        try {
            $zip->extractTo($tempExtractDir);
            $zip->close();

            return $this->parseExtractedEpub($tempExtractDir, $projectId);
        } finally {
            // Clean up raw extracted temp files
            if (File::isDirectory($tempExtractDir)) {
                File::deleteDirectory($tempExtractDir);
            }
        }
    }

    /**
     * Parse extracted EPUB folder structure and generate scene pairs.
     *
     * @return array<int, array{sequence_order: int, image_path: string|null, script_text: string}>
     */
    protected function parseExtractedEpub(string $extractDir, int $projectId): array
    {
        // 1. Locate content.opf from META-INF/container.xml
        $containerPath = $extractDir.'/META-INF/container.xml';
        $opfRelativePath = 'content.opf';

        if (File::exists($containerPath)) {
            $containerXml = File::get($containerPath);
            $containerDom = new DOMDocument;
            @$containerDom->loadXML($containerXml);
            $xpath = new DOMXPath($containerDom);
            $xpath->registerNamespace('c', 'urn:oasis:names:tc:opendocument:xmlns:container');
            $rootfiles = $xpath->query('//c:rootfile/@full-path');

            if ($rootfiles && $rootfiles->length > 0) {
                $opfRelativePath = $rootfiles->item(0)?->nodeValue ?? 'content.opf';
            }
        }

        $opfFullPath = $extractDir.'/'.$opfRelativePath;
        if (! File::exists($opfFullPath)) {
            // Fallback: search for any .opf file in the extracted directory
            $allOpf = File::glob($extractDir.'/**/*.opf');
            if (! empty($allOpf)) {
                $opfFullPath = $allOpf[0];
                $opfRelativePath = ltrim(str_replace($extractDir, '', $opfFullPath), '/\\');
            } else {
                throw new Exception('No package document (.opf) found in EPUB archive.');
            }
        }

        $opfDir = dirname($opfFullPath);
        $opfContent = File::get($opfFullPath);

        $opfDom = new DOMDocument;
        @$opfDom->loadXML($opfContent);

        // 2. Map manifest items (id => href, media-type)
        $manifest = [];
        $coverImageHref = null;
        $items = $opfDom->getElementsByTagName('item');

        foreach ($items as $item) {
            $id = $item->getAttribute('id');
            $href = $item->getAttribute('href');
            $mediaType = $item->getAttribute('media-type');
            $properties = $item->getAttribute('properties');

            $manifest[$id] = [
                'href' => $href,
                'media_type' => $mediaType,
            ];

            if ($properties === 'cover-image' || str_contains(strtolower($id), 'cover') || str_contains(strtolower($href), 'cover')) {
                if (str_starts_with($mediaType, 'image/')) {
                    $coverImageHref = $href;
                }
            }
        }

        // 3. Extract and persist all image files to storage/app/public/epub_projects/{projectId}/images/
        $storedImagesMap = []; // original href => public storage relative path
        $publicImageDir = "epub_projects/{$projectId}/images";
        $publicImageAbsDir = Storage::disk('public')->path($publicImageDir);
        File::ensureDirectoryExists($publicImageAbsDir);

        foreach ($manifest as $item) {
            if (str_starts_with($item['media_type'], 'image/')) {
                $imgSourcePath = $this->resolvePath($opfDir, $item['href']);
                if (File::exists($imgSourcePath)) {
                    $imgFilename = 'img_'.uniqid().'_'.basename($item['href']);
                    $destRelPath = "{$publicImageDir}/{$imgFilename}";
                    $destAbsPath = Storage::disk('public')->path($destRelPath);

                    File::copy($imgSourcePath, $destAbsPath);
                    $storedImagesMap[$item['href']] = $destRelPath;
                    $storedImagesMap[basename($item['href'])] = $destRelPath;
                }
            }
        }

        $defaultCoverPath = null;
        if ($coverImageHref && isset($storedImagesMap[$coverImageHref])) {
            $defaultCoverPath = $storedImagesMap[$coverImageHref];
        } elseif (! empty($storedImagesMap)) {
            $defaultCoverPath = reset($storedImagesMap);
        }

        // 4. Identify reading order from spine
        $spineItemIds = [];
        $itemrefs = $opfDom->getElementsByTagName('itemref');
        foreach ($itemrefs as $ref) {
            $idref = $ref->getAttribute('idref');
            if (isset($manifest[$idref]) && str_contains($manifest[$idref]['media_type'], 'html')) {
                $spineItemIds[] = $idref;
            }
        }

        // 5. Parse chapters and extract scene pairs using DOMDocument & DOMXPath
        $scenes = [];
        $sequenceOrder = 1;
        $currentContextImage = $defaultCoverPath;

        foreach ($spineItemIds as $idref) {
            $chapterHref = $manifest[$idref]['href'];
            $chapterAbsPath = $this->resolvePath($opfDir, $chapterHref);

            if (! File::exists($chapterAbsPath)) {
                continue;
            }

            $chapterHtml = File::get($chapterAbsPath);
            $chapterDom = new DOMDocument;
            libxml_use_internal_errors(true);
            @$chapterDom->loadHTML(mb_convert_encoding($chapterHtml, 'HTML-ENTITIES', 'UTF-8'));
            libxml_clear_errors();

            $xpath = new DOMXPath($chapterDom);
            $nodes = $xpath->query('//body//*[self::p or self::h1 or self::h2 or self::h3 or self::img or self::image]');

            if ($nodes) {
                foreach ($nodes as $node) {
                    if (! ($node instanceof DOMElement)) {
                        continue;
                    }

                    $tagName = strtolower($node->tagName);

                    if ($tagName === 'img' || $tagName === 'image') {
                        $src = $node->getAttribute('src') ?: $node->getAttribute('xlink:href');
                        $baseSrc = basename($src);
                        if (isset($storedImagesMap[$src])) {
                            $currentContextImage = $storedImagesMap[$src];
                        } elseif (isset($storedImagesMap[$baseSrc])) {
                            $currentContextImage = $storedImagesMap[$baseSrc];
                        }
                    } elseif (in_array($tagName, ['p', 'h1', 'h2', 'h3'])) {
                        $text = trim(preg_replace('/\s+/', ' ', $node->textContent));
                        if (strlen($text) >= 15) {
                            $scenes[] = [
                                'sequence_order' => $sequenceOrder++,
                                'image_path' => $currentContextImage ?: $defaultCoverPath,
                                'script_text' => $text,
                            ];
                        }
                    }
                }
            }
        }

        // Fallback: If no scenes extracted from xpath, extract from raw regex
        if (empty($scenes)) {
            foreach ($spineItemIds as $idref) {
                $chapterHref = $manifest[$idref]['href'];
                $chapterAbsPath = $this->resolvePath($opfDir, $chapterHref);
                if (! File::exists($chapterAbsPath)) {
                    continue;
                }

                $chapterHtml = File::get($chapterAbsPath);
                preg_match_all('/<p[^>]*>(.*?)<\/p>/is', $chapterHtml, $pMatches);

                if (! empty($pMatches[1])) {
                    foreach ($pMatches[1] as $rawP) {
                        $text = trim(strip_tags($rawP));
                        $text = trim(preg_replace('/\s+/', ' ', html_entity_decode($text, ENT_QUOTES, 'UTF-8')));
                        if (strlen($text) >= 15) {
                            $scenes[] = [
                                'sequence_order' => $sequenceOrder++,
                                'image_path' => $defaultCoverPath,
                                'script_text' => $text,
                            ];
                        }
                    }
                }
            }
        }

        return $scenes;
    }

    /**
     * Resolve relative file path within EPUB directory.
     */
    protected function resolvePath(string $baseDir, string $relativeHref): string
    {
        $href = rawurldecode($relativeHref);
        $full = $baseDir.'/'.$href;

        // Normalize directory separators and resolve relative parent steps (..)
        $parts = explode('/', str_replace('\\', '/', $full));
        $resolved = [];
        foreach ($parts as $part) {
            if ($part === '' || $part === '.') {
                continue;
            }
            if ($part === '..') {
                array_pop($resolved);
            } else {
                $resolved[] = $part;
            }
        }

        // On Windows or Linux, handle leading slash / drive letter
        $firstPart = reset($parts);
        $prefix = (str_contains($firstPart, ':')) ? '' : DIRECTORY_SEPARATOR;

        return $prefix.implode(DIRECTORY_SEPARATOR, $resolved);
    }
}
