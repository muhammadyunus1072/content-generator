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
        // Environments / Locations Table
        Schema::create('environments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('content_title_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('setting_type')->default('interior'); // interior, exterior, urban, fantasy, sci-fi
            $table->string('time_of_day')->default('night'); // day, golden_hour, night, twilight, atmospheric
            $table->string('atmosphere')->nullable(); // e.g. "Crimson fog, eerie silence, neon reflections"
            $table->string('lighting')->nullable(); // e.g. "Dim subterranean lighting, bioluminescent purple crystals"
            $table->text('prompt_keywords'); // SDXL-optimized environment tokens
            $table->text('description')->nullable();
            $table->string('image_path')->nullable(); // Concept art preview
            $table->timestamps();
        });

        // Key Props / Items Table
        Schema::create('props', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('content_title_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('category')->default('artifact'); // weapon, artifact, tech, clothing, vehicle
            $table->text('description')->nullable();
            $table->text('prompt_keywords'); // SDXL-optimized prop tokens
            $table->string('image_path')->nullable(); // Concept art preview
            $table->timestamps();
        });

        // Add Art Direction fields to Content Titles
        Schema::table('content_titles', function (Blueprint $table) {
            $table->string('color_palette')->nullable()->after('visual_style'); // e.g. "Dark obsidian, neon purple, electric cyan"
            $table->string('lighting_mood')->nullable()->after('color_palette'); // e.g. "Cinematic dramatic volumetric lighting"
            $table->string('camera_framing')->nullable()->after('lighting_mood'); // e.g. "Webtoon cinematic dynamic action angles"
        });

        // Add Environment and Asset attribution to Scene Boards
        Schema::table('scene_boards', function (Blueprint $table) {
            $table->foreignId('environment_id')->nullable()->after('scene_id')->constrained('environments')->nullOnDelete();
            $table->json('characters_present')->nullable()->after('environment_id');
            $table->json('props_present')->nullable()->after('characters_present');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scene_boards', function (Blueprint $table) {
            $table->dropConstrainedForeignId('environment_id');
            $table->dropColumn(['characters_present', 'props_present']);
        });

        Schema::table('content_titles', function (Blueprint $table) {
            $table->dropColumn(['color_palette', 'lighting_mood', 'camera_framing']);
        });

        Schema::dropIfExists('props');
        Schema::dropIfExists('environments');
    }
};
