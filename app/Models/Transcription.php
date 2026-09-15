<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'video_id',
    'transcript_text',
    'speaker_segments',
    'timeline',
])]
class Transcription extends Model
{
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'speaker_segments' => 'array',
            'timeline' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the video that owns the transcription.
     */
    public function video(): BelongsTo
    {
        return $this->belongsTo(Video::class);
    }

    /**
     * Convert JSON speaker segments to standard SRT format.
     */
    public function toSrt(): string
    {
        $segments = $this->speaker_segments ?? [];
        $srt = '';
        $index = 1;

        foreach ($segments as $segment) {
            $start = $segment['start'] ?? 0.0;
            $end = $segment['end'] ?? 0.0;
            $text = trim($segment['text'] ?? '');
            $speaker = $segment['speaker'] ?? null;

            if ($text === '') {
                continue;
            }

            $startTimeCode = $this->formatSecondsToSrtTime($start);
            $endTimeCode = $this->formatSecondsToSrtTime($end);

            $srt .= $index."\n";
            $srt .= $startTimeCode.' --> '.$endTimeCode."\n";

            if ($speaker) {
                $srt .= '['.$speaker.'] '.$text."\n\n";
            } else {
                $srt .= $text."\n\n";
            }
            $index++;
        }

        return rtrim($srt);
    }

    /**
     * Format decimal seconds to SRT time code (HH:MM:SS,mmm).
     */
    private function formatSecondsToSrtTime(float $seconds): string
    {
        $hours = (int) floor($seconds / 3600);
        $minutes = (int) floor(($seconds - ($hours * 3600)) / 60);
        $remainingSeconds = $seconds - ($hours * 3600) - ($minutes * 60);
        $wholeSeconds = (int) floor($remainingSeconds);
        $milliseconds = (int) round(($remainingSeconds - $wholeSeconds) * 1000);

        return sprintf(
            '%02d:%02d:%02d,%03d',
            $hours,
            $minutes,
            $wholeSeconds,
            $milliseconds
        );
    }
}
