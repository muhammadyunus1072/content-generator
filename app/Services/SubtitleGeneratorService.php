<?php

namespace App\Services;

class SubtitleGeneratorService
{
    /**
     * Maximum character count per line on screen for standard video subtitles.
     */
    public const MAX_CHARS_PER_LINE = 45;

    /**
     * Maximum number of lines per subtitle cue.
     */
    public const MAX_LINES_PER_CUE = 2;

    /**
     * Maximum character capacity per subtitle cue (2 lines * 45 chars).
     */
    public const MAX_CHARS_PER_CUE = self::MAX_CHARS_PER_LINE * self::MAX_LINES_PER_CUE;

    /**
     * Minimum display duration (in seconds) for any subtitle cue to ensure readability.
     */
    public const MIN_CUE_DURATION = 0.4;

    /**
     * Generate a perfectly formatted and timed .srt subtitle string from input text and total audio duration.
     *
     * @param  string  $text  The narrative or dialogue text.
     * @param  float  $totalAudioDuration  The exact audio duration in seconds.
     * @return string A valid SRT formatted string.
     */
    public function generate(string $text, float $totalAudioDuration): string
    {
        $text = trim($text);
        if ($text === '' || $totalAudioDuration <= 0.0) {
            return '';
        }

        // 1. Logical Chunking: Break text into readable phrases respecting natural pauses and length limits
        $chunks = $this->chunkText($text, self::MAX_CHARS_PER_LINE, self::MAX_LINES_PER_CUE);
        if (empty($chunks)) {
            return '';
        }

        // 2. Proportional Character-Based Timing Calculation
        $timedCues = $this->calculateTimings($chunks, $totalAudioDuration);

        // 3. SRT Formatting: Build sequence blocks with timestamps and uppercase text
        return $this->buildSrtString($timedCues);
    }

    /**
     * Alias for generate() method.
     */
    public function generateSrt(string $text, float $totalAudioDuration): string
    {
        return $this->generate($text, $totalAudioDuration);
    }

    /**
     * Split text into readable subtitle chunks.
     *
     * Logic:
     * - First, split at natural pauses (periods, commas, exclamation marks, question marks, semicolons, colons, dashes).
     * - If any segment exceeds the maximum length (~45-90 chars), split at the nearest space/word boundary.
     * - Ensure each final cue has at most 2 lines of text (with ~45 chars per line).
     * - Convert text to UPPERCASE per aesthetic guidelines.
     *
     * @return array<int, string> Array of formatted, uppercase subtitle cue texts.
     */
    public function chunkText(string $text, int $maxCharsPerLine = self::MAX_CHARS_PER_LINE, int $maxLinesPerCue = self::MAX_LINES_PER_CUE): array
    {
        // Normalize all whitespace (newlines, tabs, multiple spaces) into single spaces
        $cleanText = preg_replace('/\s+/u', ' ', trim($text));
        if ($cleanText === null || $cleanText === '') {
            return [];
        }

        $maxCharsPerCue = $maxCharsPerLine * $maxLinesPerCue;

        // Step 1: Split text at natural punctuation pauses while keeping the punctuation attached
        $rawSegments = preg_split('/(?<=[.!?,;:—–])\s+/u', $cleanText, -1, PREG_SPLIT_NO_EMPTY);
        if (! is_array($rawSegments) || empty($rawSegments)) {
            $rawSegments = [$cleanText];
        }

        $logicalSegments = [];

        // Step 2: Handle long segments exceeding max cue capacity by splitting at nearest space
        foreach ($rawSegments as $segment) {
            $segment = trim($segment);
            if ($segment === '') {
                continue;
            }

            if (mb_strlen($segment, 'UTF-8') <= $maxCharsPerCue) {
                $logicalSegments[] = $segment;
            } else {
                // Segment is too long: split into word-bounded sub-chunks
                $subChunks = $this->splitLongSegmentAtSpaces($segment, $maxCharsPerCue);
                foreach ($subChunks as $subChunk) {
                    $logicalSegments[] = $subChunk;
                }
            }
        }

        // Step 3: Format each segment to at most 2 lines (max ~45 chars/line) and convert to UPPERCASE
        $finalChunks = [];
        foreach ($logicalSegments as $segment) {
            $formattedCue = $this->formatCueLines($segment, $maxCharsPerLine, $maxLinesPerCue);
            if ($formattedCue !== '') {
                $finalChunks[] = mb_strtoupper($formattedCue, 'UTF-8');
            }
        }

        return $finalChunks;
    }

    /**
     * Split a long segment of text into smaller word-bounded chunks that fit within maxChars.
     *
     * @return array<int, string>
     */
    protected function splitLongSegmentAtSpaces(string $text, int $maxChars): array
    {
        $words = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY);
        if (! is_array($words) || empty($words)) {
            return [$text];
        }

        $chunks = [];
        $currentChunk = '';

        foreach ($words as $word) {
            $testChunk = ($currentChunk === '') ? $word : ($currentChunk.' '.$word);

            if (mb_strlen($testChunk, 'UTF-8') <= $maxChars) {
                $currentChunk = $testChunk;
            } else {
                if ($currentChunk !== '') {
                    $chunks[] = $currentChunk;
                }
                $currentChunk = $word;
            }
        }

        if ($currentChunk !== '') {
            $chunks[] = $currentChunk;
        }

        return $chunks;
    }

    /**
     * Format a single subtitle chunk text so it never exceeds maxLines (wrapping at maxCharsPerLine).
     */
    protected function formatCueLines(string $text, int $maxCharsPerLine, int $maxLines): string
    {
        $text = trim($text);
        if (mb_strlen($text, 'UTF-8') <= $maxCharsPerLine) {
            return $text;
        }

        // Word wrap to maximum line length
        $wrapped = wordwrap($text, $maxCharsPerLine, "\n", false);
        $lines = explode("\n", $wrapped);

        // If wrapped lines exceed maxLines, condense or take maxLines
        if (count($lines) > $maxLines) {
            $firstPart = array_slice($lines, 0, $maxLines - 1);
            $remaining = implode(' ', array_slice($lines, $maxLines - 1));
            $lines = array_merge($firstPart, [$remaining]);
        }

        return implode("\n", array_map('trim', $lines));
    }

    /**
     * Convert an Edge-TTS WebVTT (.vtt) subtitle content string directly into a clean,
     * uppercase, wrapped .srt format string with exact audio-synchronized timings.
     */
    public function convertVttToSrt(string $vttContent): string
    {
        $vttContent = trim($vttContent);
        if ($vttContent === '') {
            return '';
        }

        // Normalize newlines
        $vttContent = str_replace(["\r\n", "\r"], "\n", $vttContent);

        // Split by blank lines to get cue blocks
        $rawBlocks = preg_split('/\n\s*\n/', $vttContent);
        if (! is_array($rawBlocks) || empty($rawBlocks)) {
            return '';
        }

        $srtBlocks = [];
        $cueIndex = 1;

        foreach ($rawBlocks as $block) {
            $block = trim($block);
            if ($block === '' || str_starts_with($block, 'WEBVTT') || str_starts_with($block, 'NOTE')) {
                continue;
            }

            $lines = explode("\n", $block);
            $timestampLineIndex = -1;
            $startSrt = null;
            $endSrt = null;

            // Find line with timecodes: 00:00:00.000 --> 00:00:00.000 or 00:00.000 --> 00:00.000
            foreach ($lines as $i => $line) {
                if (preg_match('/(?:(\d{1,2}):)?(\d{2}):(\d{2})[.,](\d{3})\s*-->\s*(?:(\d{1,2}):)?(\d{2}):(\d{2})[.,](\d{3})/', $line, $matches)) {
                    $timestampLineIndex = $i;
                    $startHours = isset($matches[1]) && $matches[1] !== '' ? (int) $matches[1] : 0;
                    $startMinutes = (int) $matches[2];
                    $startSeconds = (int) $matches[3];
                    $startMs = (int) $matches[4];

                    $endHours = isset($matches[5]) && $matches[5] !== '' ? (int) $matches[5] : 0;
                    $endMinutes = (int) $matches[6];
                    $endSeconds = (int) $matches[7];
                    $endMs = (int) $matches[8];

                    $startSrt = sprintf('%02d:%02d:%02d,%03d', $startHours, $startMinutes, $startSeconds, $startMs);
                    $endSrt = sprintf('%02d:%02d:%02d,%03d', $endHours, $endMinutes, $endSeconds, $endMs);
                    break;
                }
            }

            if ($timestampLineIndex === -1 || ! $startSrt || ! $endSrt) {
                continue;
            }

            // Extract cue text lines after timestamp
            $textLines = array_slice($lines, $timestampLineIndex + 1);
            $rawText = implode(' ', $textLines);

            // Strip any WebVTT tags like <v ...>, <c ...>, or positioning directives
            $cleanText = preg_replace('/<[^>]+>/', '', $rawText) ?? $rawText;

            // Clean dramatic punctuation artifacts (e.g. trailing "..." replaced with clean text or single dot)
            $cleanText = preg_replace('/\s*\.{3,}\s*/u', ' ', $cleanText) ?? $cleanText;
            $cleanText = preg_replace('/\s*,\s*,+/u', ',', $cleanText) ?? $cleanText;
            $cleanText = preg_replace('/\s+/u', ' ', trim($cleanText)) ?? $cleanText;

            if ($cleanText === '') {
                continue;
            }

            // Format cue lines (wrap at MAX_CHARS_PER_LINE, max 2 lines, uppercase)
            $formattedText = $this->formatCueLines($cleanText, self::MAX_CHARS_PER_LINE, self::MAX_LINES_PER_CUE);
            $upperText = mb_strtoupper($formattedText, 'UTF-8');

            $srtBlocks[] = "{$cueIndex}\n{$startSrt} --> {$endSrt}\n{$upperText}";
            $cueIndex++;
        }

        return ! empty($srtBlocks) ? implode("\n\n", $srtBlocks)."\n" : '';
    }

    /**
     * Calculate start and end timestamps for each chunk using Pause-Weighted Character-Based Timing.
     *
     * Takes into account natural speech pauses at punctuation marks (periods, ellipses, commas)
     * so that algorithmic timing matches Edge-TTS cadence without linear drift.
     *
     * @param  array<int, string>  $chunks
     * @return array<int, array{text: string, start: float, end: float}>
     */
    public function calculateTimings(array $chunks, float $totalAudioDuration): array
    {
        if (empty($chunks) || $totalAudioDuration <= 0.0) {
            return [];
        }

        // Calculate character count and pause weighting of each chunk
        $weights = [];
        foreach ($chunks as $chunk) {
            $plainText = preg_replace('/\s+/u', ' ', $chunk);
            $charCount = max(1, mb_strlen((string) $plainText, 'UTF-8'));

            // Extra pause weight based on ending punctuation for natural pacing
            $pauseBonus = 0;
            if (preg_match('/[.!?…]\s*$/u', $chunk)) {
                $pauseBonus = 14; // ~0.6-0.8s dramatic pause
            } elseif (preg_match('/[,;:—–]\s*$/u', $chunk)) {
                $pauseBonus = 7;  // ~0.3-0.4s comma breathing pause
            }

            $weights[] = $charCount + $pauseBonus;
        }

        $totalWeight = (float) max(1, array_sum($weights));
        $totalChunks = count($chunks);
        $cues = [];
        $currentTimestamp = 0.0;

        foreach ($chunks as $index => $chunkText) {
            $weight = $weights[$index];

            // Calculate proportional duration
            $proportionalDuration = ($weight / $totalWeight) * $totalAudioDuration;

            $startTime = $currentTimestamp;
            $endTime = $currentTimestamp + $proportionalDuration;

            // Anchor the final cue to exactly the total audio duration
            if ($index === $totalChunks - 1) {
                $endTime = $totalAudioDuration;
            }

            // Ensure duration is always positive and strictly forward-moving
            if ($endTime <= $startTime) {
                $endTime = $startTime + self::MIN_CUE_DURATION;
            }

            $cues[] = [
                'text' => $chunkText,
                'start' => $startTime,
                'end' => $endTime,
            ];

            $currentTimestamp = $endTime;
        }

        return $cues;
    }

    /**
     * Convert calculated cue objects into a standard, clean .srt subtitle format string.
     *
     * @param  array<int, array{text: string, start: float, end: float}>  $cues
     */
    public function buildSrtString(array $cues): string
    {
        if (empty($cues)) {
            return '';
        }

        $srtBlocks = [];

        foreach ($cues as $index => $cue) {
            $sequenceNumber = $index + 1;
            $startTimecode = $this->formatTimecode($cue['start']);
            $endTimecode = $this->formatTimecode($cue['end']);
            $text = trim($cue['text']);

            $srtBlocks[] = "{$sequenceNumber}\n{$startTimecode} --> {$endTimecode}\n{$text}";
        }

        return implode("\n\n", $srtBlocks)."\n";
    }

    /**
     * Format seconds (float) into strict SRT timecode format: HH:MM:SS,mmm
     *
     * Examples:
     *   1.250 -> 00:00:01,250
     *   65.500 -> 00:01:05,500
     *   3661.020 -> 01:01:01,020
     */
    public function formatTimecode(float $seconds): string
    {
        $seconds = max(0.0, $seconds);

        $hours = (int) floor($seconds / 3600);
        $minutes = (int) floor(($seconds % 3600) / 60);
        $wholeSeconds = (int) floor($seconds % 60);
        $milliseconds = (int) round(($seconds - floor($seconds)) * 1000);

        if ($milliseconds >= 1000) {
            $wholeSeconds += (int) floor($milliseconds / 1000);
            $milliseconds = $milliseconds % 1000;
        }

        if ($wholeSeconds >= 60) {
            $minutes += (int) floor($wholeSeconds / 60);
            $wholeSeconds = $wholeSeconds % 60;
        }

        if ($minutes >= 60) {
            $hours += (int) floor($minutes / 60);
            $minutes = $minutes % 60;
        }

        return sprintf('%02d:%02d:%02d,%03d', $hours, $minutes, $wholeSeconds, $milliseconds);
    }
}
