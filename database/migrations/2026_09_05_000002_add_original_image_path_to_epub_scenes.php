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
        Schema::table('epub_scenes', function (Blueprint $table) {
            $table->string('original_image_path')->nullable()->after('sequence_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('epub_scenes', function (Blueprint $table) {
            $table->dropColumn('original_image_path');
        });
    }
};
