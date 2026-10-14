<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('setlist_songs', function (Blueprint $table) {
            // Tempo used for the song in this setlist (the band's click); null: the song's own BPM.
            $table->decimal('bpm', 5, 1)->nullable()->after('musical_key');
        });
    }

    public function down(): void
    {
        Schema::table('setlist_songs', function (Blueprint $table) {
            $table->dropColumn('bpm');
        });
    }
};
