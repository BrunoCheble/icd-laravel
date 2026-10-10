<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Versions of a song's chord map for an instrument (e.g. the bass line): an independent copy of the map made from
     * it, with the same blocks and times and its own chords, in the key it was saved in.
     */
    public function up(): void
    {
        Schema::create('song_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('song_id')->constrained()->cascadeOnDelete();
            $table->string('instrument', 50);
            $table->string('musical_key', 20)->nullable();
            $table->json('structure');
            $table->timestamps();

            $table->unique(['song_id', 'instrument']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('song_versions');
    }
};
