<?php

namespace App\Services;

use Exception;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class TranscriptionService
{
    /**
     * Transcribe an audio file using WhisperX.
     *
     * @param  string  $audioFullPath  Absolute path to the audio file on disk.
     * @param  string  $outputDir  Absolute path to the output directory where WhisperX will write JSON.
     * @return array{text: string, segments: array, words: array}
     *
     * @throws Exception
     */
    public function transcribe(string $audioFullPath, string $outputDir): array
    {
        $whisperxPath = env('WHISPERX_PATH', 'whisperx');
        $model = env('WHISPERX_MODEL', 'base');
        $computeType = env('WHISPERX_COMPUTE_TYPE', 'int8');
        $hfToken = env('HF_TOKEN');

        $command = [
            $whisperxPath,
            $audioFullPath,
            '--model', $model,
            '--compute_type', $computeType,
            '--output_dir', $outputDir,
            '--output_format', 'json',
        ];

        // Append diarisation options if a Hugging Face token is provided
        if (! empty($hfToken)) {
            $command[] = '--diarize';
            $command[] = '--hf_token';
            $command[] = $hfToken;
        }

        // Run the whisperx process
        $process = new Process($command);
        $process->setTimeout(3600); // 1-hour timeout for long audio processes
        $process->run();

        if (! $process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        // Locate the generated JSON file
        $filename = pathinfo($audioFullPath, PATHINFO_FILENAME);
        $jsonPath = rtrim($outputDir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$filename.'.json';

        if (! file_exists($jsonPath)) {
            throw new Exception("WhisperX completed execution but output file was not found at: {$jsonPath}");
        }

        $jsonContent = file_get_contents($jsonPath);
        $data = json_decode($jsonContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Failed to decode WhisperX output JSON: '.json_last_error_msg());
        }

        // Reconstruct or fetch full text
        $transcriptText = $data['text'] ?? '';
        if (empty($transcriptText) && isset($data['segments'])) {
            $texts = [];
            foreach ($data['segments'] as $segment) {
                if (isset($segment['text'])) {
                    $texts[] = trim($segment['text']);
                }
            }
            $transcriptText = implode(' ', $texts);
        }

        $segments = $data['segments'] ?? [];

        // Extract individual word-level timestamps if present
        $words = [];
        foreach ($segments as $segment) {
            if (isset($segment['words'])) {
                foreach ($segment['words'] as $word) {
                    $words[] = $word;
                }
            }
        }

        // Clean up the temporary JSON file to avoid cluttering storage
        @unlink($jsonPath);

        return [
            'text' => $transcriptText,
            'segments' => $segments,
            'words' => $words,
        ];
    }
}
