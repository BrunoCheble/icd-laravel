<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The song's old video lessons (links edited on the song form) are gone: lessons are kept in song_lessons now
     * (lessons tab). Their content is not carried over.
     */
    public function up(): void
    {
        Schema::table('songs', function (Blueprint $table) {
            $table->dropColumn('video_lesson');
        });
    }

    public function down(): void
    {
        Schema::table('songs', function (Blueprint $table) {
            $table->json('video_lesson')->nullable();
        });
    }
};
