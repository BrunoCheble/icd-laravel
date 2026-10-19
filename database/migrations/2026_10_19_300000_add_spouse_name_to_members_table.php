<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The spouse's name when the spouse is not a member of the church (a member spouse is spouse_id).
     */
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->string('spouse_name')->nullable()->after('spouse_id');
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn('spouse_name');
        });
    }
};
