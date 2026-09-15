<?php

namespace App\Livewire;

use App\Jobs\CutClipJob;
use App\Jobs\DownloadVideoJob;
use App\Jobs\ExtractAudioJob;
use App\Models\Video;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;

class VideoUpload extends Component
{
    use WithFileUploads;

    /**
     * The input mode: 'file' or 'url'.
     */
    public string $mode = 'file';

    /**
     * The video file to be uploaded.
     */
    public $videoFile;

    /**
     * The online video URL to download.
     */
    public string $videoUrl = '';

    /**
     * Optional custom title for the video.
     */
    public string $title = '';

    /**
     * The clip generation mode: 'manual' or 'automatic'.
     */
    public string $clipMode = 'manual';

    /**
     * The list of manual timestamp ranges for clipping.
     *
     * @var array<int, array{start: float|string, end: float|string}>
     */
    public array $manualClips = [
        ['start' => 0, 'end' => 30],
    ];

    /**
     * Add a new manual clip timestamp row.
     */
    public function addManualClip(): void
    {
        $lastClip = end($this->manualClips);
        $newStart = is_array($lastClip) && isset($lastClip['end']) && is_numeric($lastClip['end'])
            ? (float) $lastClip['end']
            : 0;

        $this->manualClips[] = [
            'start' => $newStart,
            'end' => $newStart + 30,
        ];
    }

    /**
     * Remove a manual clip timestamp row.
     */
    public function removeManualClip(int $index): void
    {
        if (count($this->manualClips) <= 1) {
            $this->manualClips = [
                ['start' => 0, 'end' => 30],
            ];

            return;
        }

        unset($this->manualClips[$index]);
        $this->manualClips = array_values($this->manualClips);
    }

    /**
     * Create manual clip records for the video.
     */
    protected function createManualClips(Video $video): void
    {
        if ($this->clipMode !== 'manual') {
            return;
        }

        foreach ($this->manualClips as $index => $clipData) {
            $video->clips()->create([
                'title' => 'Clip #'.($index + 1),
                'start_seconds' => (float) $clipData['start'],
                'end_seconds' => (float) $clipData['end'],
                'status' => 'pending',
            ]);
        }
    }

    /**
     * Save the uploaded video or queue a URL download and start processing.
     */
    public function save(): void
    {
        $rules = [
            'title' => 'nullable|string|max:255',
            'clipMode' => 'required|in:manual,automatic',
        ];

        if ($this->clipMode === 'manual') {
            $rules['manualClips'] = 'required|array|min:1';
            $rules['manualClips.*.start'] = 'required|numeric|min:0';
            $rules['manualClips.*.end'] = 'required|numeric|gt:manualClips.*.start';
        }

        if ($this->mode === 'file') {
            $rules['videoFile'] = 'required|file|mimes:mp4,mov,avi,mkv,webm|max:512000';
            $this->validate($rules, [
                'manualClips.*.start.required' => __('Start time is required.'),
                'manualClips.*.start.numeric' => __('Start time must be a number.'),
                'manualClips.*.end.required' => __('End time is required.'),
                'manualClips.*.end.numeric' => __('End time must be a number.'),
                'manualClips.*.end.gt' => __('End time must be greater than start time.'),
            ]);

            // Save original video to the private 'local' disk
            $path = $this->videoFile->store('videos', 'local');

            // Create the video record
            $video = Video::create([
                'user_id' => Auth::id(),
                'title' => $this->title ?: pathinfo($this->videoFile->getClientOriginalName(), PATHINFO_FILENAME),
                'original_filename' => $this->videoFile->getClientOriginalName(),
                'video_path' => $path,
                'status' => $this->clipMode === 'manual' ? 'completed' : 'pending',
                'processing_mode' => $this->clipMode,
            ]);

            $this->createManualClips($video);

            if ($this->clipMode === 'manual') {
                foreach ($video->clips as $clip) {
                    $clip->update(['status' => 'processing']);
                    CutClipJob::dispatch($clip);
                }

                Flux::toast(
                    variant: 'success',
                    text: __('Video uploaded! Manual clipping started in the background.')
                );
            } else {
                // Dispatch background audio extraction job
                ExtractAudioJob::dispatch($video);

                // Notify user via Flux toast
                Flux::toast(
                    variant: 'success',
                    text: __('Video uploaded successfully! Background processing has started.')
                );
            }
        } else {
            $rules['videoUrl'] = 'required|url';
            $this->validate($rules, [
                'manualClips.*.start.required' => __('Start time is required.'),
                'manualClips.*.start.numeric' => __('Start time must be a number.'),
                'manualClips.*.end.required' => __('End time is required.'),
                'manualClips.*.end.numeric' => __('End time must be a number.'),
                'manualClips.*.end.gt' => __('End time must be greater than start time.'),
            ]);

            // Create the video record in downloading state
            $video = Video::create([
                'user_id' => Auth::id(),
                'title' => $this->title,
                'original_filename' => $this->videoUrl,
                'video_path' => '',
                'status' => 'downloading',
                'processing_mode' => $this->clipMode,
            ]);

            $this->createManualClips($video);

            // Dispatch background download job
            DownloadVideoJob::dispatch($video, $this->videoUrl);

            // Notify user via Flux toast
            Flux::toast(
                variant: 'success',
                text: $this->clipMode === 'manual'
                    ? __('Video download scheduled! Manual clips will be generated once downloaded.')
                    : __('Video download scheduled in the background!')
            );
        }

        $this->reset(['videoFile', 'videoUrl', 'title']);
        $this->manualClips = [['start' => 0, 'end' => 30]];

        // Dispatch browser event to notify parent components to refresh list
        $this->dispatch('video-uploaded');
    }

    /**
     * Render the component view.
     */
    public function render()
    {
        return view('livewire.video-upload');
    }
}
