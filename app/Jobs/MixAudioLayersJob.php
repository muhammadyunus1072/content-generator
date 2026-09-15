<?php

namespace App\Jobs;

use App\Models\SceneBoard;
use App\Services\AudioMixerService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class MixAudioLayersJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 300;

    public function __construct(public SceneBoard $board) {}

    public function handle(AudioMixerService $mixer): void
    {
        try {
            $mixedAudioPath = $mixer->mixBoardAudio($this->board);
            // If the audio mixer created a new mixed track, we can track it
            Log::info('Audio mixed for board', ['board_id' => $this->board->id, 'path' => $mixedAudioPath]);
        } catch (Exception $e) {
            Log::error('MixAudioLayersJob failed', [
                'board_id' => $this->board->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
