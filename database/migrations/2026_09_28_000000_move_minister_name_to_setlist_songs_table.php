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
            $table->string('minister_name')->nullable()->after('position');
        });

        // Each song of a setlist inherits the minister previously set on the setlist.
        DB::statement(<<<'SQL'
            UPDATE setlist_songs ss
            JOIN setlists s ON s.id = ss.setlist_id
            SET ss.minister_name = s.minister_name
        SQL);

        Schema::table('setlists', function (Blueprint $table) {
            $table->dropColumn('minister_name');
        });
    }

    public function down(): void
    {
        Schema::table('setlists', function (Blueprint $table) {
            $table->string('minister_name')->nullable()->after('title');
        });

        // Best effort: the setlist gets the minister of its first song.
        DB::statement(<<<'SQL'
            UPDATE setlists s
            SET s.minister_name = (
                SELECT ss.minister_name FROM setlist_songs ss
                WHERE ss.setlist_id = s.id AND ss.minister_name IS NOT NULL
                ORDER BY ss.position
                LIMIT 1
            )
        SQL);

        Schema::table('setlist_songs', function (Blueprint $table) {
            $table->dropColumn('minister_name');
        });
    }
};
