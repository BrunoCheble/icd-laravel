<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The mobile number left with a prayer request (optional), to answer it by WhatsApp.
     */
    public function up(): void
    {
        Schema::table('prayers', function (Blueprint $table) {
            $table->string('phone_number', 20)->nullable()->after('request');
        });
    }

    public function down(): void
    {
        Schema::table('prayers', function (Blueprint $table) {
            $table->dropColumn('phone_number');
        });
    }
};
