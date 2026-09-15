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
        Schema::table('episodes', function (Blueprint $table) {
            $table->string('webtoon_strip_path')->nullable()->after('duration_seconds');
            $table->json('webtoon_slices')->nullable()->after('webtoon_strip_path');
            $table->string('webtoon_status')->default('pending')->after('webtoon_slices');
            $table->string('webtoon_export_pack_path')->nullable()->after('webtoon_status');
        });

        Schema::table('scene_boards', function (Blueprint $table) {
            $table->json('sfx_typography')->nullable()->after('sfx_tags');
            $table->unsignedInteger('gutter_spacing')->default(80)->after('sfx_typography');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scene_boards', function (Blueprint $table) {
            $table->dropColumn(['sfx_typography', 'gutter_spacing']);
        });

        Schema::table('episodes', function (Blueprint $table) {
            $table->dropColumn([
                'webtoon_strip_path',
                'webtoon_slices',
                'webtoon_status',
                'webtoon_export_pack_path',
            ]);
        });
    }
};
