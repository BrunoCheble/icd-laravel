<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('setlist_songs', function (Blueprint $table) {
            $table->string('musical_key', 20)->nullable()->after('song_id');
            $table->unsignedInteger('position')->nullable()->after('musical_key');
        });

        // Backfill existing links: key from the song, position following the previous (alphabetical) order.
        DB::statement(<<<'SQL'
            UPDATE setlist_songs ss
            JOIN (
                SELECT x.setlist_id, x.song_id, s.musical_key,
                       ROW_NUMBER() OVER (PARTITION BY x.setlist_id ORDER BY s.title, s.id) AS rn
                FROM setlist_songs x
                JOIN songs s ON s.id = x.song_id
            ) r ON r.setlist_id = ss.setlist_id AND r.song_id = ss.song_id
            SET ss.position = r.rn, ss.musical_key = r.musical_key
        SQL);

        Schema::table('setlist_songs', function (Blueprint $table) {
            $table->unsignedInteger('position')->nullable(false)->change();
            $table->index(['setlist_id', 'position'], 'idx_setlist_position');
        });
    }

    public function down(): void
    {
        Schema::table('setlist_songs', function (Blueprint $table) {
            $table->dropIndex('idx_setlist_position');
            $table->dropColumn(['musical_key', 'position']);
        });
    }
};
