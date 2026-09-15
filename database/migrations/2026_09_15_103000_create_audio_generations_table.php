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
        Schema::create('audio_generations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->text('text');
            $table->string('provider')->default('edge_tts'); // 'edge_tts', 'docker_openai', 'docker_cog', 'elevenlabs'
            $table->string('voice')->default('id-ID-ArdiNeural');
            $table->string('voice_name')->nullable();
            $table->string('rate')->nullable();
            $table->string('pitch')->nullable();
            $table->string('audio_path');
            $table->string('subtitle_path')->nullable();
            $table->json('subtitle_cues')->nullable();
            $table->float('duration_seconds')->default(0);
            $table->unsignedBigInteger('file_size_bytes')->default(0);
            $table->json('options')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audio_generations');
    }
};
