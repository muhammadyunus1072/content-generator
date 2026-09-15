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
        Schema::table('scene_boards', function (Blueprint $table) {
            $table->json('dialogue_audio_timeline')->nullable()->after('duration_seconds');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scene_boards', function (Blueprint $table) {
            $table->dropColumn(['dialogue_audio_timeline']);
        });
    }
};
