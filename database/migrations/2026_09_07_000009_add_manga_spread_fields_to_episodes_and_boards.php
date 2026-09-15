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
            $table->json('manga_pages')->nullable()->after('webtoon_export_pack_path');
            $table->string('manga_status')->default('pending')->after('manga_pages'); // pending, generating, completed, failed
            $table->string('manga_reading_direction')->default('rtl')->after('manga_status'); // rtl (manga) or ltr (comic)
            $table->string('cbz_package_path')->nullable()->after('manga_reading_direction');
            $table->string('pdf_package_path')->nullable()->after('cbz_package_path');
        });

        Schema::table('scene_boards', function (Blueprint $table) {
            $table->string('page_layout_hint')->default('auto')->after('gutter_spacing'); // auto, splash, wide_half, small_quarter
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('episodes', function (Blueprint $table) {
            $table->dropColumn([
                'manga_pages',
                'manga_status',
                'manga_reading_direction',
                'cbz_package_path',
                'pdf_package_path',
            ]);
        });

        Schema::table('scene_boards', function (Blueprint $table) {
            $table->dropColumn(['page_layout_hint']);
        });
    }
};
