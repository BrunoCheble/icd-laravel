<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tempo of the song in beats per minute: the live mode of the setlist app counts the bars with it.
     */
    public function up(): void
    {
        Schema::table('songs', function (Blueprint $table) {
            $table->decimal('bpm', 5, 1)->nullable()->after('musical_key');
        });
    }

    public function down(): void
    {
        Schema::table('songs', function (Blueprint $table) {
            $table->dropColumn('bpm');
        });
    }
};
