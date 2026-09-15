<?php

namespace App\Jobs;

use App\Models\Video;
use Exception;
use FFMpeg\Format\Audio\Mp3;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\WithQueue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;

#[WithQueue(
    timeout: 1800, // 30 minutes timeout for long video processing
    tries: 2
)]
class ExtractAudioJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public Video $video) {}

    public function handle(): void
    {
        $this->video->update([
            'status' => 'extracting_audio',
            'error_message' => null,
        ]);

        try {
            // Verify source video exists before processing
            if (! Storage::disk('local')->exists($this->video->video_path)) {
                throw new Exception("Source video file not found at: {$this->video->video_path}");
            }

            // Sanitize filename to prevent illegal character issues
            $rawName = pathinfo($this->video->original_filename, PATHINFO_FILENAME);
            $safeName = Str::slug($rawName) ?: 'audio';
            $audioPath = 'audio/'.$safeName.'_'.time().'.mp3';

            // Extract audio and output to destination disk
            FFMpeg::fromDisk('local')
                ->open($this->video->video_path)
                ->export()
                ->toDisk('public')
                ->inFormat((new Mp3)->setAudioKiloBitrate(128)) // 128 kbps is optimal for Whisper STT
                ->save($audioPath);

            $this->video->update([
                'audio_path' => $audioPath,
                'status' => 'audio_extracted',
            ]);

            // Dispatch next step
            GenerateSubtitlesJob::dispatch($this->video);
        } catch (Exception $e) {
            $this->video->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
