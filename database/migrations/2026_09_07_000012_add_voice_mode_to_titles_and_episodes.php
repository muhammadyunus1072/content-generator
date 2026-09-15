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
            $table->string('voice_mode')->default('multi_actor')->after('voice_name');
        });

        Schema::table('episodes', function (Blueprint $table) {
            $table->string('voice_mode')->nullable()->after('status');
        });

        Schema::table('content_factory_templates', function (Blueprint $table) {
            $table->string('voice_mode')->default('multi_actor')->after('voice_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('content_factory_templates', function (Blueprint $table) {
            $table->dropColumn(['voice_mode']);
        });

        Schema::table('episodes', function (Blueprint $table) {
            $table->dropColumn(['voice_mode']);
        });

        Schema::table('content_titles', function (Blueprint $table) {
            $table->dropColumn(['voice_mode']);
        });
    }
};
