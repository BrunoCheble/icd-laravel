<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('songs', function (Blueprint $table) {
            // Set by hand once the song's map has been reviewed.
            $table->timestamp('reviewed_at')->nullable()->after('chord_sheet');
        });
    }

    public function down(): void
    {
        Schema::table('songs', function (Blueprint $table) {
            $table->dropColumn('reviewed_at');
        });
    }
};
