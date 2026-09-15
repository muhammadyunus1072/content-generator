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
        Schema::table('epub_projects', function (Blueprint $table) {
            $table->string('source_language')->default('auto')->after('aspect_ratio');
            $table->string('target_language')->default('en')->after('source_language');
        });

        Schema::table('epub_scenes', function (Blueprint $table) {
            $table->longText('original_script_text')->nullable()->after('image_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('epub_scenes', function (Blueprint $table) {
            $table->dropColumn('original_script_text');
        });

        Schema::table('epub_projects', function (Blueprint $table) {
            $table->dropColumn(['source_language', 'target_language']);
        });
    }
};
