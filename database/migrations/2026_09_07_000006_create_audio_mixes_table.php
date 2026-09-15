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
        Schema::create('audio_mixes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scene_board_id')->constrained()->cascadeOnDelete();
            $table->string('layer_type'); // bgm, sfx, ambient, voice
            $table->string('audio_path');
            $table->decimal('volume', 3, 2)->default(1.00); // 0.00 - 1.00
            $table->decimal('start_offset', 8, 2)->default(0.00); // seconds offset
            $table->decimal('fade_in', 5, 2)->default(0.00);
            $table->decimal('fade_out', 5, 2)->default(0.00);
            $table->boolean('loop')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audio_mixes');
    }
};
