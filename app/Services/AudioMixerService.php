<?php

namespace App\Services;

use App\Models\AudioMix;
use App\Models\SceneBoard;
use Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class AudioMixerService
{
    /**
     * Mood-to-BGM presets mapping.
     */
    public const MOOD_BGM_MAP = [
        'action' => 'audio_library/bgm/action_epic.mp3',
        'romance' => 'audio_library/bgm/romance_gentle.mp3',
        'suspense' => 'audio_library/bgm/suspense_tension.mp3',
        'comedy' => 'audio_library/bgm/comedy_light.mp3',
        'horror' => 'audio_library/bgm/horror_dark.mp3',
        'calm' => 'audio_library/bgm/calm_ambient.mp3',
    ];

    /**
     * SFX Tag to audio file mapping.
     */
    public const SFX_LIBRARY = [
        'slash' => 'audio_library/sfx/slash_metal.mp3',
        'explosion' => 'audio_library/sfx/explosion_heavy.mp3',
        'heartbeat' => 'audio_library/sfx/heartbeat_slow.mp3',
        'thunder' => 'audio_library/sfx/thunder_crack.mp3',
        'footsteps' => 'audio_library/sfx/footsteps_stone.mp3',
        'wind' => 'audio_library/sfx/wind_howl.mp3',
        'rain' => 'audio_library/sfx/rain_ambient.mp3',
        'sword_draw' => 'audio_library/sfx/sword_draw.mp3',
        'punch' => 'audio_library/sfx/punch_impact.mp3',
        'magic_cast' => 'audio_library/sfx/magic_cast.mp3',
    ];

    /**
     * Mix audio layers for a SceneBoard into a single high-quality audio file.
     *
     * @return string Relative storage path to the mixed audio file
     */
    public function mixBoardAudio(SceneBoard $board, ?string $customVoicePath = null): string
    {
        $voicePath = $customVoicePath ?? $board->audio_path;

        if (! $voicePath) {
            throw new Exception('Cannot mix audio: SceneBoard has no voice audio track.');
        }

        $voiceAbsPath = $this->resolveAbsolutePath($voicePath);

        if (! File::exists($voiceAbsPath)) {
            throw new Exception("Voice audio file does not exist at: {$voiceAbsPath}");
        }

        $ffmpegBin = env('FFMPEG_BINARIES', 'C:/ffmpeg/bin/ffmpeg.exe');
        $outputRelative = 'stories/mixed_audio/board_'.$board->id.'_'.uniqid().'.mp3';
        $outputAbsPath = Storage::disk('public')->path($outputRelative);
        File::ensureDirectoryExists(dirname($outputAbsPath));

        // Find available layers
        $layers = $board->audioMixes()->get();
        $bgmAbsPath = null;
        $bgmVolume = 0.20;
        $sfxAbsPath = null;
        $sfxVolume = 0.70;

        // Check if database AudioMix records exist
        foreach ($layers as $layer) {
            $layerAbs = $this->resolveAbsolutePath($layer->audio_path);
            if (File::exists($layerAbs)) {
                if ($layer->layer_type === 'bgm') {
                    $bgmAbsPath = $layerAbs;
                    $bgmVolume = $layer->volume;
                } elseif ($layer->layer_type === 'sfx') {
                    $sfxAbsPath = $layerAbs;
                    $sfxVolume = $layer->volume;
                }
            }
        }

        // Fallback: check mood_tag for default BGM if no explicit BGM layer
        if (! $bgmAbsPath && $board->mood_tag) {
            $defaultBgmKey = strtolower($board->mood_tag);
            if (isset(self::MOOD_BGM_MAP[$defaultBgmKey])) {
                $possibleBgm = Storage::disk('public')->path(self::MOOD_BGM_MAP[$defaultBgmKey]);
                if (File::exists($possibleBgm)) {
                    $bgmAbsPath = $possibleBgm;
                }
            }
        }

        // Fallback: check sfx_tags for first matching SFX if no explicit SFX layer
        if (! $sfxAbsPath && ! empty($board->sfx_tags) && is_array($board->sfx_tags)) {
            foreach ($board->sfx_tags as $sfxTag) {
                $tagKey = strtolower(trim($sfxTag));
                if (isset(self::SFX_LIBRARY[$tagKey])) {
                    $possibleSfx = Storage::disk('public')->path(self::SFX_LIBRARY[$tagKey]);
                    if (File::exists($possibleSfx)) {
                        $sfxAbsPath = $possibleSfx;
                        break;
                    }
                }
            }
        }

        // If no extra layers exist, simply copy/symlink or return the voice audio path
        if (! $bgmAbsPath && ! $sfxAbsPath) {
            return $voicePath;
        }

        // Build FFmpeg command with complex audio filter
        $inputs = ['-i', $voiceAbsPath];
        $filterComplex = '[0:a]volume=1.0[voice];';
        $inputIndex = 1;
        $mixList = '[voice]';
        $totalMixInputs = 1;

        if ($bgmAbsPath) {
            $inputs[] = '-stream_loop';
            $inputs[] = '-1';
            $inputs[] = '-i';
            $inputs[] = $bgmAbsPath;
            $filterComplex .= "[{$inputIndex}:a]volume={$bgmVolume}[bgm];";
            $mixList .= '[bgm]';
            $inputIndex++;
            $totalMixInputs++;
        }

        if ($sfxAbsPath) {
            $inputs[] = '-i';
            $inputs[] = $sfxAbsPath;
            $filterComplex .= "[{$inputIndex}:a]volume={$sfxVolume}[sfx];";
            $mixList .= '[sfx]';
            $inputIndex++;
            $totalMixInputs++;
        }

        $filterComplex .= "{$mixList}amix=inputs={$totalMixInputs}:duration=first:dropout_transition=2[aout]";

        $cmd = array_merge(
            [$ffmpegBin, '-y'],
            $inputs,
            [
                '-filter_complex', $filterComplex,
                '-map', '[aout]',
                '-c:a', 'libmp3lame',
                '-b:a', '192k',
                $outputAbsPath,
            ]
        );

        $process = new Process($cmd);
        $process->setTimeout(120);
        $process->run();

        if (! $process->isSuccessful() || ! File::exists($outputAbsPath) || filesize($outputAbsPath) === 0) {
            Log::warning('AudioMixerService FFmpeg failed, falling back to original voice', [
                'error' => $process->getErrorOutput(),
            ]);

            return $voicePath;
        }

        return $outputRelative;
    }

    /**
     * Resolve file path to absolute path.
     */
    protected function resolveAbsolutePath(string $path): string
    {
        if (File::exists($path)) {
            return $path;
        }

        return Storage::disk('public')->path($path);
    }
}
