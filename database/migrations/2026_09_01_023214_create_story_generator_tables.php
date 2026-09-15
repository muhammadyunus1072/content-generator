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
        Schema::create('stories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->longText('premise');
            $table->string('genre')->default('Action Fantasy');
            $table->string('visual_style')->default('Dark Fantasy Manhwa');
            $table->string('aspect_ratio')->default('16:9');
            $table->string('voice_name')->default('en-US-ChristopherNeural');
            $table->string('language')->default('en');
            $table->string('status')->default('draft');
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        Schema::create('episodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('story_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('episode_number')->default(1);
            $table->string('title');
            $table->text('synopsis')->nullable();
            $table->longText('narrative_script')->nullable();
            $table->string('status')->default('draft');
            $table->string('video_path')->nullable();
            $table->string('audio_path')->nullable();
            $table->string('subtitle_path')->nullable();
            $table->decimal('duration_seconds', 8, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('scenes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('episode_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('scene_number')->default(1);
            $table->string('title')->nullable();
            $table->string('setting')->nullable();
            $table->string('mood')->nullable();
            $table->text('narration_text')->nullable();
            $table->timestamps();
        });

        Schema::create('scene_boards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('episode_id')->constrained()->cascadeOnDelete();
            $table->foreignId('scene_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('board_number')->default(1);
            $table->unsignedInteger('sequence_order')->default(0);
            $table->text('narration_chunk');
            $table->string('view_type')->default('wide_shot');
            $table->string('camera_movement')->default('zoom_in');
            $table->text('visual_prompt');
            $table->string('image_path')->nullable();
            $table->string('audio_path')->nullable();
            $table->decimal('duration_seconds', 8, 2)->default(4.00);
            $table->decimal('start_seconds', 8, 2)->default(0.00);
            $table->decimal('end_seconds', 8, 2)->default(0.00);
            $table->string('status')->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scene_boards');
        Schema::dropIfExists('scenes');
        Schema::dropIfExists('episodes');
        Schema::dropIfExists('stories');
    }
};
