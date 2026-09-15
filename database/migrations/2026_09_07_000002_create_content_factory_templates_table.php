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
        Schema::create('content_factory_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('niche'); // manhwa_recap, kids_story, science_explainer, horror_narration, book_summary
            $table->string('icon')->default('sparkles');
            $table->text('description');
            $table->string('visual_style');
            $table->string('color_palette')->nullable();
            $table->string('lighting_mood')->nullable();
            $table->string('camera_framing')->nullable();
            $table->string('genre');
            $table->string('voice_name');
            $table->string('tts_rate')->nullable();
            $table->string('tts_pitch')->nullable();
            $table->string('aspect_ratio')->default('16:9');
            $table->string('target_platform')->default('both'); // youtube, tiktok, both
            $table->text('prompt_template');
            $table->text('negative_prompt')->nullable();
            $table->json('monetization_tips')->nullable();
            $table->json('example_titles')->nullable();
            $table->decimal('estimated_rpm_min', 8, 2)->nullable();
            $table->decimal('estimated_rpm_max', 8, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('content_factory_templates');
    }
};
