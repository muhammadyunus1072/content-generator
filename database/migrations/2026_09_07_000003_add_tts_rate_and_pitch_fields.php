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
        Schema::table('content_titles', function (Blueprint $table) {
            $table->string('tts_rate')->nullable()->after('voice_name');
            $table->string('tts_pitch')->nullable()->after('tts_rate');
        });

        Schema::table('stories', function (Blueprint $table) {
            $table->string('tts_rate')->nullable()->after('voice_name');
            $table->string('tts_pitch')->nullable()->after('tts_rate');
        });

        Schema::table('epub_projects', function (Blueprint $table) {
            $table->string('tts_rate')->nullable()->after('voice_name');
            $table->string('tts_pitch')->nullable()->after('tts_rate');
        });

        Schema::table('epub_scenes', function (Blueprint $table) {
            $table->string('subtitle_path')->nullable()->after('audio_duration');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('content_titles', function (Blueprint $table) {
            $table->dropColumn(['tts_rate', 'tts_pitch']);
        });

        Schema::table('stories', function (Blueprint $table) {
            $table->dropColumn(['tts_rate', 'tts_pitch']);
        });

        Schema::table('epub_projects', function (Blueprint $table) {
            $table->dropColumn(['tts_rate', 'tts_pitch']);
        });

        Schema::table('epub_scenes', function (Blueprint $table) {
            $table->dropColumn(['subtitle_path']);
        });
    }
};
