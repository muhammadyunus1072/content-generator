<?php

namespace App\Jobs;

use App\Models\SceneBoard;
use App\Services\SpeechBubbleGeneratorService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class GenerateSpeechBubbleJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 180;

    public function __construct(public SceneBoard $board) {}

    public function handle(SpeechBubbleGeneratorService $generator): void
    {
        if (empty($this->board->speech_bubbles) || empty($this->board->image_path)) {
            return;
        }

        try {
            $outputRelative = 'stories/comic_panels/board_'.$this->board->id.'_bubble.png';
            $result = $generator->renderBubblesOnImage(
                $this->board->image_path,
                $this->board->speech_bubbles,
                $outputRelative
            );

            $this->board->update([
                'speech_bubble_image_path' => $result,
            ]);
        } catch (Exception $e) {
            Log::error('GenerateSpeechBubbleJob failed', [
                'board_id' => $this->board->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
