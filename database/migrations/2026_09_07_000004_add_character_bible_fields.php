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
            $table->string('voice_id')->nullable()->after('prompt_keywords');
            $table->string('tts_provider')->nullable()->default('edge-tts')->after('voice_id');
            $table->string('color_theme')->nullable()->after('tts_provider');
            $table->text('personality')->nullable()->after('color_theme');
            $table->json('relationships')->nullable()->after('personality');
            $table->string('role')->nullable()->default('supporting')->after('relationships');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('characters', function (Blueprint $table) {
            $table->dropColumn([
                'voice_id',
                'tts_provider',
                'color_theme',
                'personality',
                'relationships',
                'role',
            ]);
        });
    }
};
