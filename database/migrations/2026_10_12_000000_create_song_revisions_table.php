<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Earlier versions of a song's chord map, chord sheet, tempo and key: one is kept every time any of them changes
     * (see RecordSongRevisionService), so they can be seen and restored.
     */
    public function up(): void
    {
        Schema::create('song_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('song_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source', 60)->nullable();
            $table->json('structure')->nullable();
            $table->json('chord_sheet')->nullable();
            $table->string('musical_key', 20)->nullable();
            $table->decimal('bpm', 5, 1)->nullable();
            $table->timestamps();

            $table->index(['song_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('song_revisions');
    }
};
