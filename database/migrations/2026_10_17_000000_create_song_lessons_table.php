<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lessons of a song for an instrument, each of one type: a YouTube video, an audio file, a PDF or an image; with
     * notes (optional), and stretches of the video or audio saved to be repeated.
     */
    public function up(): void
    {
        Schema::create('song_lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('song_id')->constrained()->cascadeOnDelete();
            $table->string('instrument', 50);
            $table->string('title', 150);
            // youtube, audio, pdf or image (see SongLesson::TYPES).
            $table->string('type', 10);
            $table->text('notes')->nullable();
            $table->string('video_url', 255)->nullable();
            $table->string('file_path', 255)->nullable();
            $table->string('file_name', 255)->nullable();
            // Stretches to repeat: [{ label, start, end }] in seconds of the video or audio.
            $table->json('loops')->nullable();
            // Sent by a musician on the public page (a YouTube link); only the team changes or removes it.
            $table->boolean('contributed')->default(false);
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();

            $table->index(['song_id', 'instrument']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('song_lessons');
    }
};
