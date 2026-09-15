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
        Schema::table('video_builder_projects', function (Blueprint $table) {
            $table->boolean('save_to_library')->default(true)->after('output_video_path');
            $table->foreignId('saved_video_id')->nullable()->after('save_to_library')->constrained('videos')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('video_builder_projects', function (Blueprint $table) {
            $table->dropForeign(['saved_video_id']);
            $table->dropColumn(['save_to_library', 'saved_video_id']);
        });
    }
};
