<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Each member in a ministry with an optional role (free text: Pastor Presidente, Vocal…), an order (how the site
     * lists them) and an optional biography (JSON, see MinistryBio). A ministry can be the site's "Pastores" (key
     * "pastoral").
     */
    public function up(): void
    {
        // The first migration made "ministry_member"; the code (and production) use "ministry_members".
        if (Schema::hasTable('ministry_member') && ! Schema::hasTable('ministry_members')) {
            Schema::rename('ministry_member', 'ministry_members');
        }

        Schema::table('ministries', function (Blueprint $table) {
            $table->string('key', 30)->nullable()->unique()->after('name');
        });

        Schema::table('ministry_members', function (Blueprint $table) {
            $table->string('role')->nullable()->change();
            $table->unsignedSmallInteger('sort_order')->default(0)->after('active');
            $table->json('bio')->nullable()->after('sort_order');
        });
    }

    public function down(): void
    {
        Schema::table('ministry_members', function (Blueprint $table) {
            $table->dropColumn(['sort_order', 'bio']);
        });
        Schema::table('ministries', function (Blueprint $table) {
            $table->dropUnique(['key']);
            $table->dropColumn('key');
        });
    }
};
