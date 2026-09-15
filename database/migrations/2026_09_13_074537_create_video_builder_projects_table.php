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
        Schema::create('video_builder_projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->string('aspect_ratio')->default('16:9'); // '16:9' or '9:16'
            $table->string('main_source_type')->default('file'); // 'file', 'library', 'url'
            $table->string('main_video_path')->nullable();
            $table->text('main_video_url')->nullable();
            $table->json('assets_config')->nullable();
            $table->string('output_video_path')->nullable();
            $table->string('status')->default('pending'); // 'pending', 'downloading_source', 'rendering', 'completed', 'failed'
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('video_builder_projects');
    }
};
