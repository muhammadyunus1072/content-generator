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
            $table->string('animation_effect')->default('ken_burns')->after('camera_movement');
            $table->string('transition_type')->default('crossfade')->after('animation_effect');
            $table->json('speech_bubbles')->nullable()->after('transition_type');
            $table->string('bubble_style')->nullable()->default('manga')->after('speech_bubbles');
            $table->json('sfx_tags')->nullable()->after('bubble_style');
            $table->string('mood_tag')->nullable()->default('action')->after('sfx_tags');
            $table->json('parallax_layers')->nullable()->after('mood_tag');
            $table->boolean('has_particles')->default(false)->after('parallax_layers');
            $table->string('particle_type')->nullable()->after('has_particles');
            $table->string('speech_bubble_image_path')->nullable()->after('image_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scene_boards', function (Blueprint $table) {
            $table->dropColumn([
                'animation_effect',
                'transition_type',
                'speech_bubbles',
                'bubble_style',
                'sfx_tags',
                'mood_tag',
                'parallax_layers',
                'has_particles',
                'particle_type',
                'speech_bubble_image_path',
            ]);
        });
    }
};
