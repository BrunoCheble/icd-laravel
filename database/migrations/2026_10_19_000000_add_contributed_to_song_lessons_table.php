<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lessons sent by a musician on the public page (`contributed`), for databases whose song_lessons table was made
     * before that column existed. Only adds the column when it is missing: no lesson is changed or removed.
     */
    public function up(): void
    {
        if (Schema::hasColumn('song_lessons', 'contributed')) {
            return;
        }

        Schema::table('song_lessons', function (Blueprint $table) {
            $table->boolean('contributed')->default(false)->after('loops');
        });
    }

    /**
     * Nothing is undone: the column also belongs to the table as created (create_song_lessons_table).
     */
    public function down(): void
    {
    }
};
