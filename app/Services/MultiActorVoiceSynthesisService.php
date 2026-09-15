<?php

namespace App\Services;

use App\Models\Character;
use App\Models\SceneBoard;
use Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class MultiActorVoiceSynthesisService
{
    /**
     * Emotion acoustic modulation map for Edge-TTS rate and pitch adjustments.
     */
    public const EMOTION_ACOUSTIC_MAP = [
        'neutral' => ['rate' => '+0%', 'pitch' => '+0Hz'],
        'intense' => ['rate' => '+15%', 'pitch' => '+8Hz'],
        'shouting' => ['rate' => '+25%', 'pitch' => '+14Hz'],
        'whisper' => ['rate' => '-15%', 'pitch' => '-6Hz'],
        'sorrow' => ['rate' => '-20%', 'pitch' => '-12Hz'],
        'joy' => ['rate' => '+15%', 'pitch' => '+6Hz'],
        'terrified' => ['rate' => '+10%', 'pitch' => '+16Hz'],
    ];

    /**
     * Standard breathing pause between dialogue actors in seconds.
     */
    public const INTER_ACTOR_PAUSE_SECONDS = 0.25;

    public function __construct(
        protected TextToSpeechService $ttsService
    ) {}

    /**
     * Resolve the optimal voice for a speaker based on Character Bible or Title fallbacks.
     *
     * @return array{voice: string, provider: string, character_id: ?int}
     */
    public function resolveSpeakerVoice(string $speaker, SceneBoard $board): array
    {
        $speakerTrimmed = trim($speaker);

        // 1. Narrator fallback
        if ($speakerTrimmed === '' || strcasecmp($speakerTrimmed, 'narrator') === 0) {
            return $this->getNarratorVoice($board);
        }

        // 2. Search for Character in Episode Story
        $story = $board->episode?->story;
        $character = null;

        if ($story) {
            $character = $story->characters()
                ->whereRaw('LOWER(name) = ?', [strtolower($speakerTrimmed)])
                ->first();
        }

        // 3. Fallback: Search characters globally by user
        if (! $character && $story?->user_id) {
            $character = Character::where('user_id', $story->user_id)
                ->whereRaw('LOWER(name) = ?', [strtolower($speakerTrimmed)])
                ->first();
        }

        // 4. Character found with explicit voice_id
        if ($character && ! empty($character->voice_id)) {
            return [
                'voice' => $character->voice_id,
                'provider' => $character->tts_provider ?? 'edge-tts',
                'character_id' => $character->id,
            ];
        }

        // 5. Character found without explicit voice_id: infer from gender
        if ($character) {
            $voice = match (strtolower($character->gender ?? 'male')) {
                'female' => 'en-US-JennyNeural',
                default => 'en-US-GuyNeural',
            };

            return [
                'voice' => $voice,
                'provider' => 'edge-tts',
                'character_id' => $character->id,
            ];
        }

        // 6. Character not found: Default to story narrator voice
        return $this->getNarratorVoice($board);
    }

    /**
     * Calculate acoustic modulation parameters (rate and pitch) for an emotion.
     *
     * @return array{rate: string, pitch: string}
     */
    public function getEmotionAcousticParams(string $emotion, ?string $baseRate = null, ?string $basePitch = null): array
    {
        $emotionLower = strtolower(trim($emotion));
        $params = self::EMOTION_ACOUSTIC_MAP[$emotionLower] ?? self::EMOTION_ACOUSTIC_MAP['neutral'];

        // If base rate/pitch is supplied, combine or use emotion parameters
        $rate = $baseRate ?: $params['rate'];
        $pitch = $basePitch ?: $params['pitch'];

        return [
            'rate' => $rate,
            'pitch' => $pitch,
        ];
    }

    /**
     * Synthesize dialogue for a SceneBoard, stitch with natural pauses,
     * generate synchronized subtitles, and update board metadata.
     *
     * @return array{audio_path: string, subtitle_path: string|null, duration_seconds: float, timeline: array}
     */
    public function synthesizeBoardDialogue(SceneBoard $board, ?string $voiceMode = null): array
    {
        $mode = $voiceMode ?? $board->episode?->getEffectiveVoiceMode() ?? 'multi_actor';
        $lines = $this->prepareDialogueLines($board, $mode);

        if (empty($lines)) {
            throw new Exception("SceneBoard #{$board->id} has no dialogue lines or narration to synthesize.");
        }

        $tempDir = storage_path('app/temp/multiactor_'.$board->id.'_'.uniqid());
        File::ensureDirectoryExists($tempDir);

        $snippets = [];
        $timeline = [];
        $currentTime = 0.0;

        try {
            foreach ($lines as $i => $line) {
                $speaker = $line['speaker'];
                $text = $line['text'];
                $emotion = $line['emotion'] ?? 'neutral';

                $voiceConfig = ! empty($line['voice_id'])
                    ? ['voice' => $line['voice_id'], 'provider' => 'edge-tts', 'character_id' => null]
                    : $this->resolveSpeakerVoice($speaker, $board);

                $acoustic = $this->getEmotionAcousticParams($emotion);

                $snippetAudioPath = $tempDir."/snippet_{$i}.mp3";
                $this->ttsService->synthesize(
                    text: $text,
                    outputAudioAbsolutePath: $snippetAudioPath,
                    outputSubtitleAbsolutePath: null,
                    voice: $voiceConfig['voice'],
                    provider: $voiceConfig['provider'],
                    rate: $acoustic['rate'],
                    pitch: $acoustic['pitch']
                );

                $snippetDuration = $this->ttsService->getAudioDuration($snippetAudioPath);

                $snippets[] = $snippetAudioPath;
                $timeline[] = [
                    'speaker' => $speaker,
                    'text' => $text,
                    'emotion' => $emotion,
                    'voice' => $voiceConfig['voice'],
                    'start_time' => round($currentTime, 2),
                    'end_time' => round($currentTime + $snippetDuration, 2),
                    'duration' => round($snippetDuration, 2),
                ];

                $currentTime += $snippetDuration + self::INTER_ACTOR_PAUSE_SECONDS;
            }

            // Concatenate snippets with breathing pauses into final output
            $finalAudioRelative = 'stories/boards/board_'.$board->id.'_multiactor_'.time().'.mp3';
            $finalSubRelative = 'stories/boards/board_'.$board->id.'_multiactor_'.time().'.vtt';

            $finalAudioAbsolute = Storage::disk('public')->path($finalAudioRelative);
            $finalSubAbsolute = Storage::disk('public')->path($finalSubRelative);
            File::ensureDirectoryExists(dirname($finalAudioAbsolute));

            $this->concatenateSnippets($snippets, $finalAudioAbsolute);
            $this->generateSynchronizedVttSubtitles($timeline, $finalSubAbsolute);

            $totalDuration = $this->ttsService->getAudioDuration($finalAudioAbsolute);

            // Persist to board
            $board->update([
                'audio_path' => $finalAudioRelative,
                'subtitle_path' => $finalSubRelative,
                'duration_seconds' => $totalDuration,
                'dialogue_audio_timeline' => $timeline,
                'status' => $board->image_path ? 'ready' : 'audio_ready',
            ]);

            return [
                'audio_path' => $finalAudioRelative,
                'subtitle_path' => $finalSubRelative,
                'duration_seconds' => $totalDuration,
                'timeline' => $timeline,
            ];
        } finally {
            if (File::exists($tempDir)) {
                File::deleteDirectory($tempDir);
            }
        }
    }

    /**
     * Extract structured dialogue lines from speech bubbles or narration chunk based on voice mode.
     *
     * @return array<int, array{speaker: string, text: string, emotion: string, voice_id: ?string}>
     */
    public function prepareDialogueLines(SceneBoard $board, ?string $voiceMode = null): array
    {
        $mode = $voiceMode ?? $board->episode?->getEffectiveVoiceMode() ?? 'multi_actor';

        // When in Solo 3rd Person Narrator mode, strictly deliver the prose narration chunk
        if ($mode === 'solo_narrator') {
            $narration = trim($board->narration_chunk ?? '');
            if ($narration !== '') {
                return [
                    [
                        'speaker' => 'Narrator',
                        'text' => $narration,
                        'emotion' => 'neutral',
                        'voice_id' => null,
                    ],
                ];
            }
        }

        $lines = [];

        if (! empty($board->speech_bubbles) && is_array($board->speech_bubbles)) {
            foreach ($board->speech_bubbles as $bubble) {
                $text = trim($bubble['text'] ?? '');
                if ($text === '') {
                    continue;
                }

                $lines[] = [
                    'speaker' => trim($bubble['speaker'] ?? 'Narrator') ?: 'Narrator',
                    'text' => $text,
                    'emotion' => $bubble['emotion'] ?? 'neutral',
                    'voice_id' => $bubble['voice_id'] ?? null,
                ];
            }
        }

        // Fallback: If no speech bubbles present, use narration_chunk
        if (empty($lines) && ! empty(trim($board->narration_chunk ?? ''))) {
            $lines[] = [
                'speaker' => 'Narrator',
                'text' => trim($board->narration_chunk),
                'emotion' => 'neutral',
                'voice_id' => null,
            ];
        }

        return $lines;
    }

    /**
     * Concatenate snippet audio files using FFmpeg complex filter with inter-actor padding.
     *
     * @param  array<int, string>  $snippets
     */
    protected function concatenateSnippets(array $snippets, string $outputPath): void
    {
        $ffmpegBin = env('FFMPEG_BINARIES', 'C:/ffmpeg/bin/ffmpeg.exe');

        if (count($snippets) === 1) {
            File::copy($snippets[0], $outputPath);

            return;
        }

        $cmd = [$ffmpegBin, '-y'];
        $filterComplex = '';
        $concatInputs = '';

        foreach ($snippets as $i => $snippet) {
            $cmd[] = '-i';
            $cmd[] = $snippet;
            // Pad inter-actor pause (except for the last actor)
            if ($i < count($snippets) - 1) {
                $filterComplex .= "[{$i}:a]apad=pad_dur=".self::INTER_ACTOR_PAUSE_SECONDS."[a{$i}];";
            } else {
                $filterComplex .= "[{$i}:a]anull[a{$i}];";
            }
            $concatInputs .= "[a{$i}]";
        }

        $filterComplex .= "{$concatInputs}concat=n=".count($snippets).':v=0:a=1[out]';

        $cmd[] = '-filter_complex';
        $cmd[] = $filterComplex;
        $cmd[] = '-map';
        $cmd[] = '[out]';
        $cmd[] = '-c:a';
        $cmd[] = 'libmp3lame';
        $cmd[] = '-q:a';
        $cmd[] = '2';
        $cmd[] = $outputPath;

        $process = new Process($cmd);
        $process->setTimeout(60);
        $process->run();

        if (! $process->isSuccessful() || ! File::exists($outputPath)) {
            Log::error('Multi-actor audio concatenation failed', [
                'error' => $process->getErrorOutput(),
            ]);
            throw new Exception('FFmpeg multi-actor concatenation failed: '.$process->getErrorOutput());
        }
    }

    /**
     * Generate standard WebVTT subtitles synchronized to the multi-actor timeline.
     *
     * @param  array<int, array{speaker: string, text: string, start_time: float, end_time: float}>  $timeline
     */
    public function generateSynchronizedVttSubtitles(array $timeline, string $outputPath): void
    {
        $content = "WEBVTT\n\n";

        foreach ($timeline as $i => $item) {
            $startFormatted = $this->formatVttTimestamp($item['start_time']);
            $endFormatted = $this->formatVttTimestamp($item['end_time']);
            $speaker = strtoupper($item['speaker'] ?: 'NARRATOR');
            $text = $item['text'];

            $content .= ($i + 1)."\n";
            $content .= "{$startFormatted} --> {$endFormatted}\n";
            $content .= "<v {$speaker}>[{$speaker}]: {$text}\n\n";
        }

        File::put($outputPath, $content);
    }

    /**
     * Format float seconds into WebVTT timestamp (HH:MM:SS.mmm).
     */
    protected function formatVttTimestamp(float $seconds): string
    {
        $hours = (int) floor($seconds / 3600);
        $minutes = (int) floor(($seconds % 3600) / 60);
        $secs = (int) floor($seconds % 60);
        $millis = (int) round(($seconds - floor($seconds)) * 1000);

        return sprintf('%02d:%02d:%02d.%03d', $hours, $minutes, $secs, $millis);
    }

    /**
     * Get default narrator voice from Story or Content Title.
     *
     * @return array{voice: string, provider: string, character_id: null}
     */
    protected function getNarratorVoice(SceneBoard $board): array
    {
        $story = $board->episode?->story;
        $title = $story?->contentTitle;

        $voice = $story?->voice_name
            ?? $title?->voice_name
            ?? 'en-US-ChristopherNeural';

        $provider = $story?->tts_provider ?? 'edge-tts';

        return [
            'voice' => $voice,
            'provider' => $provider,
            'character_id' => null,
        ];
    }
}
