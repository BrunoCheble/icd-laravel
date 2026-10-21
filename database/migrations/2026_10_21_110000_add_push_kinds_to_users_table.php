<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The kinds of notification a user wants (see SendPushDigestService::KINDS); null: all of them.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('push_kinds')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('push_kinds');
        });
    }
};
