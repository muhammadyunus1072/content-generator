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
            $table->dropColumn('visual_prompt');
            $table->json('scene_data')->nullable()->after('narration_chunk');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scene_boards', function (Blueprint $table) {
            $table->dropColumn('scene_data');
            $table->text('visual_prompt')->nullable()->after('narration_chunk');
        });
    }
};
