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
        Schema::create('epub_projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('epub_path');
            $table->string('status')->default('uploaded'); // uploaded, parsing, generating_audio, rendering_scenes, merging_episode, completed, failed
            $table->string('aspect_ratio')->default('16:9'); // 16:9, 9:16
            $table->string('voice_name')->default('en-US-ChristopherNeural');
            $table->string('output_video_path')->nullable();
            $table->unsignedInteger('total_scenes')->default(0);
            $table->unsignedInteger('rendered_scenes')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamps();
        });

        Schema::create('epub_scenes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('epub_project_id')->constrained('epub_projects')->cascadeOnDelete();
            $table->unsignedInteger('sequence_order')->default(0);
            $table->string('image_path')->nullable();
            $table->longText('script_text');
            $table->string('audio_path')->nullable();
            $table->decimal('audio_duration', 8, 2)->nullable();
            $table->string('video_path')->nullable();
            $table->string('status')->default('pending'); // pending, generating_audio, audio_ready, rendering_video, completed, failed
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('epub_scenes');
        Schema::dropIfExists('epub_projects');
    }
};
