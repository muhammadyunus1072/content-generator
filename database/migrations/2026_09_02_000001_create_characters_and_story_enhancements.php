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
        Schema::create('characters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('gender')->nullable();
            $table->unsignedInteger('age')->nullable();
            $table->text('appearance')->nullable();
            $table->text('prompt_keywords')->nullable();
            $table->string('avatar_path')->nullable();
            $table->timestamps();
        });

        Schema::create('character_story', function (Blueprint $table) {
            $table->id();
            $table->foreignId('story_id')->constrained()->cascadeOnDelete();
            $table->foreignId('character_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::table('stories', function (Blueprint $table) {
            $table->string('image_provider')->default('cloudflare')->after('visual_style');
            $table->string('tts_provider')->default('edge-tts')->after('voice_name');
        });

        Schema::table('scene_boards', function (Blueprint $table) {
            $table->string('subtitle_path')->nullable()->after('audio_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scene_boards', function (Blueprint $table) {
            $table->dropColumn('subtitle_path');
        });

        Schema::table('stories', function (Blueprint $table) {
            $table->dropColumn(['image_provider', 'tts_provider']);
        });

        Schema::dropIfExists('character_story');
        Schema::dropIfExists('characters');
    }
};
