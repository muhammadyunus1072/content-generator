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
            $table->foreignId('image_connection_id')->nullable()->after('image_provider')->constrained('ai_connections')->nullOnDelete();
            $table->foreignId('text_connection_id')->nullable()->after('image_connection_id')->constrained('ai_connections')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('content_titles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('image_connection_id');
            $table->dropConstrainedForeignId('text_connection_id');
        });
    }
};
