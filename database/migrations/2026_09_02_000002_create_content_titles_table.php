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
        Schema::create('content_titles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('genre')->default('Action Fantasy');
            $table->string('visual_style')->default('Dark Fantasy Manhwa');
            $table->string('aspect_ratio')->default('9:16');
            $table->string('voice_name')->default('id-ID-ArdiNeural');
            $table->string('image_provider')->default('cloudflare');
            $table->string('tts_provider')->default('edge-tts');
            $table->string('cover_image_path')->nullable();
            $table->timestamps();
        });

        Schema::create('character_content_title', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_title_id')->constrained()->cascadeOnDelete();
            $table->foreignId('character_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::table('stories', function (Blueprint $table) {
            $table->foreignId('content_title_id')->nullable()->after('user_id')->constrained('content_titles')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stories', function (Blueprint $table) {
            $table->dropConstrainedForeignId('content_title_id');
        });

        Schema::dropIfExists('character_content_title');
        Schema::dropIfExists('content_titles');
    }
};
