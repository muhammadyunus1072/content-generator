<?php

namespace App\Jobs;

use App\Models\Transcription;
use App\Models\Video;
use App\Services\TranscriptionService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class GenerateSubtitlesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 900;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 2;

    /**
     * Create a new job instance.
     */
    public function __construct(public Video $video) {}

    /**
     * Execute the job.
     */
    public function handle(TranscriptionService $transcriptionService): void
    {
        $this->video->update([
            'status' => 'transcribing',
            'error_message' => null,
        ]);

        try {
            if (! $this->video->audio_path) {
                throw new Exception('Cannot transcribe video: audio path is not set.');
            }

            // Resolve paths using Laravel Storage
            $audioFullPath = Storage::disk('public')->path($this->video->audio_path);
            $outputDir = Storage::disk('public')->path('audio');

            if (! file_exists($audioFullPath)) {
                throw new Exception("Audio file not found at: {$audioFullPath}");
            }

            // Execute transcription
            $data = $transcriptionService->transcribe($audioFullPath, $outputDir);

            // Update or create transcription database record
            Transcription::updateOrCreate(
                ['video_id' => $this->video->id],
                [
                    'transcript_text' => $data['text'],
                    'speaker_segments' => $data['segments'],
                    'timeline' => $data['words'],
                ]
            );

            // Update video status to transcribed
            $this->video->update([
                'status' => 'transcribed',
            ]);

            // Dispatch next step
            GenerateContentJob::dispatch($this->video);
        } catch (Exception $e) {
            $this->video->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
