<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ai_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name'); // e.g. "Together AI - FLUX.1 Schnell", "Gemini 2.0 Flash"
            $table->string('category'); // text, image, audio_tts, audio_stt
            $table->string('provider'); // gemini, openai_compatible, cloudflare, pollinations, together_ai, fal_ai, groq, elevenlabs, edge_tts, custom_http
            $table->string('model_id'); // model identifier string
            $table->text('api_key')->nullable(); // encrypted
            $table->string('base_url')->nullable(); // custom endpoint or base API URL
            $table->json('headers')->nullable(); // custom auth headers or secrets
            $table->json('options')->nullable(); // temperature, steps, guidance_scale, etc.
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_tested_at')->nullable();
            $table->string('last_test_status')->nullable(); // success, failed
            $table->text('last_test_message')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_connections');
    }
};
