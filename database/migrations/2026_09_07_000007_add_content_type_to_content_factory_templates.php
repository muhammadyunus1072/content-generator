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
        Schema::table('content_factory_templates', function (Blueprint $table) {
            $table->string('content_type')->default('story_video')->after('niche'); // story_video, motion_comic, webtoon_strip, animated_panels
            $table->json('default_animation_config')->nullable()->after('target_platform');
            $table->json('audio_config')->nullable()->after('default_animation_config');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('content_factory_templates', function (Blueprint $table) {
            $table->dropColumn([
                'content_type',
                'default_animation_config',
                'audio_config',
            ]);
        });
    }
};
