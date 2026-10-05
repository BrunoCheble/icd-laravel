<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('songs', function (Blueprint $table) {
            // Full chord sheet (chords positioned over the lyrics), kept apart from the "structure" chord map.
            $table->json('chord_sheet')->nullable()->after('structure');
        });
    }

    public function down(): void
    {
        Schema::table('songs', function (Blueprint $table) {
            $table->dropColumn('chord_sheet');
        });
    }
};
