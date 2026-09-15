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
        Schema::table('characters', function (Blueprint $table) {
            $table->string('entity_type')->default('human')->after('gender'); // human, creature, animal, android, etc.
            $table->string('sheet_image_path')->nullable()->after('concept_art_path');
            $table->json('sheet_data')->nullable()->after('sheet_image_path');
        });

        Schema::table('environments', function (Blueprint $table) {
            $table->string('sheet_image_path')->nullable()->after('concept_art_path');
            $table->json('sheet_data')->nullable()->after('sheet_image_path');
        });

        Schema::table('props', function (Blueprint $table) {
            $table->string('sheet_image_path')->nullable()->after('concept_art_path');
            $table->json('sheet_data')->nullable()->after('sheet_image_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('characters', function (Blueprint $table) {
            $table->dropColumn(['entity_type', 'sheet_image_path', 'sheet_data']);
        });

        Schema::table('environments', function (Blueprint $table) {
            $table->dropColumn(['sheet_image_path', 'sheet_data']);
        });

        Schema::table('props', function (Blueprint $table) {
            $table->dropColumn(['sheet_image_path', 'sheet_data']);
        });
    }
};
