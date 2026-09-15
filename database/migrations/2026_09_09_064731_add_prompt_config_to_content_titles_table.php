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
        Schema::table('content_titles', function (Blueprint $table) {
            $table->json('prompt_config')->nullable()->after('visual_style');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('content_titles', function (Blueprint $table) {
            $table->dropColumn('prompt_config');
        });
    }
};
