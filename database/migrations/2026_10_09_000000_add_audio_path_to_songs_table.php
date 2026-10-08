<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Audio file of the song (public/audio/songs), played instead of the YouTube video and kept for offline use.
     */
    public function up(): void
    {
        Schema::table('songs', function (Blueprint $table) {
            $table->string('audio_path')->nullable()->after('youtube_url');
        });
    }

    public function down(): void
    {
        Schema::table('songs', function (Blueprint $table) {
            $table->dropColumn('audio_path');
        });
    }
};
