<?php

namespace App\AiServices\Services;

use App\Models\Ichijikin\IchijikinExtractionFile;
use Gemini\Data\Blob;
use Gemini\Data\Content;
use Gemini\Data\GenerationConfig;
use Gemini\Data\Schema;
use Gemini\Data\ThinkingConfig;
use Gemini\Enums\DataType;
use Gemini\Enums\MimeType;
use Gemini\Enums\ResponseMimeType;
use Gemini\Enums\ThinkingLevel;
use Gemini\Laravel\Facades\Gemini;
use Illuminate\Support\Facades\Log;

class IchijikinExtractionService
{
    public function extract(IchijikinExtractionFile $ichijikin)
    {
        // 1. Define the Strict JSON Schema
        // All fields are explicitly nullable to match database migrations and missing image fallbacks.
        $schema = new Schema(
            type: DataType::OBJECT,
            properties: [
                'kokumin' => new Schema(
                    type: DataType::INTEGER,
                    description: 'Extract numerical value. Remove commas and currency symbols. If blank, missing, or empty, return 0.',
                ),
                'nama_lengkap' => new Schema(
                    type: DataType::STRING,
                    description: 'Extract full name verbatim. Pay extreme attention to double letters (e.g., ZZ, AA, RR). Return null if missing.',
                    nullable: true
                ),
                'nenkin_20' => new Schema(
                    type: DataType::INTEGER,
                    description: 'Remove commas and currency symbols (円). Example: 20,547円 -> 20547. Return 0 if missing.',
                ),
                'nenkin_80' => new Schema(
                    type: DataType::INTEGER,
                    description: 'Remove commas and currency symbols (円). Example: 20,547円 -> 20547. Return 0 if missing.',
                ),
                'nenkin_100' => new Schema(
                    type: DataType::INTEGER,
                    description: 'Remove commas and currency symbols (円). Example: 20,547円 -> 20547. Return 0 if missing.',
                ),
                'no_nenkin' => new Schema(
                    type: DataType::STRING,
                    description: 'Extract raw digits only. Remove spaces and punctuation. DO NOT remove leading zeros. Example: "0160 618 880" -> "0160618880". Return null if missing.',
                    nullable: true
                ),
                'lama_kerja' => new Schema(
                    type: DataType::INTEGER,
                    description: 'Remove all spaces. Example: 48. Return 0 if missing.',
                ),
                'lama_kerja_kokumin' => new Schema(
                    type: DataType::INTEGER,
                    description: 'Remove all spaces. Example: 48. Return 0 if missing.',
                ),
                'alamat' => new Schema(
                    type: DataType::STRING,
                    description: 'Extract the full address exactly as shown. Return null if missing.',
                    nullable: true
                ),
                'confidence_score' => new Schema(
                    type: DataType::INTEGER,
                    description: 'Provide a score from 1 to 100 based on readability. Return 0 if ALL crops are blank or missing.'
                ),
                'confidence_note' => new Schema(
                    type: DataType::STRING,
                    description: 'IF confidence_score < 100, explain the reason. ELSE, return null.',
                    nullable: true
                ),
            ],
            // Keeping them in 'required' forces Gemini to always output the JSON keys,
            // but 'nullable: true' allows the values to be null.
            required: [
                'kokumin',
                'nama_lengkap',
                'nenkin_20',
                'nenkin_80',
                'nenkin_100',
                'no_nenkin',
                'lama_kerja',
                'lama_kerja_kokumin',
                'alamat',
                'confidence_score',
            ]
        );

        // 2. Define the System Instruction (Context + Behavior)
        $systemInstruction = "You are a precise data extraction AI. The document contains Japanese text (Kanji, Kana, Romaji) and numerical financial data.
        
        CRITICAL TRANSCRIPTION RULES:
        1. Act as a verbatim transcription tool. Do NOT assume common spellings or autocorrect names.
        2. Perform a strict character-by-character copy.
        3. If a field's image is blank, entirely unreadable, or flagged as 'IMAGE MISSING', you MUST return null for that field.
        
        Return the data ONLY as a valid JSON object matching the requested schema.";

        // 3. Define the Prompt (Task specific rules)
        $promptParts = $this->getPrompt();

        // 4. Interleave Field Names and Image Blobs
        $files = [
            'kokumin' => 'kokumin.png',
            'nama_lengkap' => 'nama_lengkap.png',
            'nenkin_20' => 'nenkin_20.png',
            'nenkin_80' => 'nenkin_80.png',
            'nenkin_100' => 'nenkin_100.png',
            'no_nenkin' => 'no_nenkin.png',
            'lama_kerja' => 'lama_kerja.png',
            'lama_kerja_kokumin' => 'lama_kerja_kokumin.png',
            'alamat' => 'alamat.png',
        ];

        $missingFilesCount = 0;

        foreach ($files as $key => $fileName) {
            $filePath = storage_path("app/public/ichijikin/{$ichijikin->ichijikinExtraction->batch_name}/{$ichijikin->ichijikinExtractionDetail->stored_name}/crop/{$ichijikin->file_stored_name}/{$fileName}");

            if (file_exists($filePath)) {
                $promptParts[] = 'Field Name: '.$key;
                $promptParts[] = new Blob(
                    mimeType: MimeType::IMAGE_PNG,
                    data: base64_encode(file_get_contents($filePath))
                );
            } else {
                // Prevent hallucination by explicitly telling the model the image is missing
                $promptParts[] = "Field Name: {$key} - [IMAGE MISSING. You MUST return null for this field].";
                $missingFilesCount++;
            }
        }

        // 5. Send Request to Gemini
        try {
            $result = Gemini::generativeModel(model: config('gemini.model'))
                ->withSystemInstruction(Content::parse($systemInstruction))
                ->withGenerationConfig(
                    generationConfig: new GenerationConfig(
                        responseMimeType: ResponseMimeType::APPLICATION_JSON,
                        responseSchema: $schema,
                        temperature: 0.0, // Strict extraction
                        // Removed MediaResolution::HIGH to save input tokens on small crops
                        thinkingConfig: new ThinkingConfig(
                            includeThoughts: true,
                            thinkingLevel: ThinkingLevel::HIGH, // Downgraded from HIGH to save output tokens
                        )
                    )
                )
                ->generateContent($promptParts);
        } catch (\Exception $e) {
            Log::error('Gemini API Error: '.$e->getMessage());

            return ['error' => 'API communication failed.'];
        }

        // 6. Parse Response Safely
        $response = [];
        $thoughts = null;
        $jsonText = null;

        foreach ($result->parts() as $part) {
            if ($part->thought === true) {
                $thoughts = $part->text;
            } elseif ($part->text !== null) {
                $jsonText = $part->text;
            }
        }

        // Bulletproof JSON decoding
        $decodedJson = json_decode($jsonText, true);
        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decodedJson)) {
            Log::error('Gemini JSON Parse Error: '.json_last_error_msg().' Raw Text: '.$jsonText);

            return ['error' => 'Invalid JSON returned from model.'];
        }

        $response['json'] = $decodedJson;
        $response['confidence_score'] = $decodedJson['confidence_score'] ?? ($missingFilesCount === count($files) ? 0 : 50);
        $response['confidence_note'] = $decodedJson['confidence_note'] ?? null;

        // 7. Token Usage & Cost Calculation
        $metadata = $result->usageMetadata;

        $response['input_tokens'] = $metadata->promptTokenCount ?? 0;
        $response['output_tokens'] = $metadata->candidatesTokenCount ?? 0;
        $response['thinking_tokens'] = $metadata->thoughtsTokenCount ?? 0;
        $response['cached_tokens'] = $metadata->cachedContentTokenCount ?? 0;
        $response['total_tokens'] = $metadata->totalTokenCount ?? 0;

        $input_price = env('GEMINI_INPUT_PRICE', 0.25);
        $output_price = env('GEMINI_OUTPUT_PRICE', 1.50);

        $response['input_cost'] = ($response['input_tokens'] / 1000000) * $input_price;
        $response['thinking_cost'] = ($response['thinking_tokens'] / 1000000) * $output_price;
        $response['output_cost'] = ($response['output_tokens'] / 1000000) * $output_price;
        // Output tokens already include thinking tokens, so total cost is just Input + Output
        $response['total_cost'] = $response['input_cost'] + $response['output_cost'];

        $response['request_payload'] = json_encode([
            'missing_files' => $missingFilesCount,
            'config' => ['temperature' => 0.0, 'thinking_level' => 'STANDARD'],
        ], true);

        $response['response_payload'] = json_encode([
            'data' => $response['json'],
            'thinking_process' => $thoughts,
        ], true);

        return $response;
    }

    private function getPrompt()
    {
        return [
            "You are an expert OCR system. I am providing multiple cropped images from a Japanese document. Each image is preceded by its field name.
            
            RULES:
            1. Extract the text from each image and map it to the corresponding field name.
            2. For 'nenkin_100', 'nenkin_80', 'nenkin_20', and 'kokumin', you MUST return an integer. Strip out all currency symbols (円), commas (,), and spaces.
            3. For 'no_nenkin', you MUST return a continuous string of digits. Do NOT remove leading zeros.
            4. If an image is blank, empty, or unreadable, return null.
            5. If a value is uncertain, choose the safest conservative interpretation and lower the confidence_score.",
        ];
    }
}
