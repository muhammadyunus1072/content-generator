<?php

namespace App\Jobs;

use App\Models\SceneBoard;
use App\Services\MultiActorVoiceSynthesisService;
use App\Services\TextToSpeechService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GenerateBoardVoiceJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 300;

    public function __construct(public SceneBoard $board) {}

    public function handle(TextToSpeechService $ttsService, MultiActorVoiceSynthesisService $multiVoiceService): void
    {
        $this->board->update(['status' => 'generating_audio']);

        try {
            $voiceMode = $this->board->episode?->getEffectiveVoiceMode() ?? 'multi_actor';

            if ($voiceMode === 'multi_actor' && ! empty($this->board->speech_bubbles) && is_array($this->board->speech_bubbles)) {
                $hasBubbleText = false;
                foreach ($this->board->speech_bubbles as $bubble) {
                    if (! empty(trim($bubble['text'] ?? ''))) {
                        $hasBubbleText = true;
                        break;
                    }
                }

                if ($hasBubbleText) {
                    $multiVoiceService->synthesizeBoardDialogue($this->board, 'multi_actor');

                    return;
                }
            }

            $story = $this->board->episode?->story;
            $voice = $story ? $story->voice_name : 'id-ID-ArdiNeural';
            $provider = $story ? $story->tts_provider : 'edge-tts';
            $rate = $story?->tts_rate;
            $pitch = $story?->tts_pitch;

            $audioRelativePath = 'stories/boards/board_'.$this->board->id.'_'.time().'.mp3';
            $subtitleRelativePath = 'stories/boards/board_'.$this->board->id.'_'.time().'.vtt';

            $audioAbsolutePath = Storage::disk('public')->path($audioRelativePath);
            $subtitleAbsolutePath = Storage::disk('public')->path($subtitleRelativePath);
            File::ensureDirectoryExists(dirname($audioAbsolutePath));

            $result = $ttsService->synthesize(
                text: $this->board->narration_chunk,
                outputAudioAbsolutePath: $audioAbsolutePath,
                outputSubtitleAbsolutePath: $subtitleAbsolutePath,
                voice: $voice,
                provider: $provider,
                rate: $rate,
                pitch: $pitch
            );

            $this->board->update([
                'audio_path' => $audioRelativePath,
                'subtitle_path' => $result['subtitle_path'] ? $subtitleRelativePath : null,
                'duration_seconds' => $result['duration_seconds'],
                'status' => $this->board->image_path ? 'ready' : 'audio_ready',
            ]);
        } catch (Exception $e) {
            Log::error('GenerateBoardVoiceJob failed', [
                'board_id' => $this->board->id,
                'error' => $e->getMessage(),
            ]);

            $this->board->update(['status' => 'failed']);
            throw $e;
        }
    }
}
