<?php

namespace App\Jobs;

use App\Models\SceneBoard;
use App\Services\MultiActorVoiceSynthesisService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class GenerateMultiActorVoiceJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 300;

    public function __construct(public SceneBoard $board) {}

    public function handle(MultiActorVoiceSynthesisService $voiceService): void
    {
        $this->board->update(['status' => 'generating_audio']);

        try {
            $voiceService->synthesizeBoardDialogue($this->board);
        } catch (Exception $e) {
            Log::error('GenerateMultiActorVoiceJob failed', [
                'board_id' => $this->board->id,
                'error' => $e->getMessage(),
            ]);

            $this->board->update(['status' => 'failed']);
            throw $e;
        }
    }
}
