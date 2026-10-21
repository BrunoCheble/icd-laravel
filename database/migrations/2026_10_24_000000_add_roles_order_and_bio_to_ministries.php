<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ministries with their own roles ("cargos": Pastor Presidente, Vocal…), and each member in a ministry with an
     * optional role, an order (how the site lists them) and an optional biography (JSON, see MinistryBio). A ministry
     * can be the site's "Pastores" (key "pastoral"). The roles typed so far become the ministry's roles.
     */
    public function up(): void
    {
        // The first migration made "ministry_member"; the code (and production) use "ministry_members".
        if (Schema::hasTable('ministry_member') && ! Schema::hasTable('ministry_members')) {
            Schema::rename('ministry_member', 'ministry_members');
        }

        Schema::create('ministry_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ministry_id')->constrained('ministries')->cascadeOnDelete();
            $table->string('name', 80);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['ministry_id', 'name']);
        });

        Schema::table('ministries', function (Blueprint $table) {
            $table->string('key', 30)->nullable()->unique()->after('name');
        });

        Schema::table('ministry_members', function (Blueprint $table) {
            $table->string('role')->nullable()->change();
            $table->foreignId('ministry_role_id')->nullable()->after('member_id')->constrained('ministry_roles')->nullOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0)->after('active');
            $table->json('bio')->nullable()->after('sort_order');
        });

        foreach (DB::table('ministry_members')->whereNotNull('role')->where('role', '!=', '')->get() as $row) {
            $name = trim($row->role);
            $role = DB::table('ministry_roles')->where('ministry_id', $row->ministry_id)->where('name', $name)->value('id')
                ?? DB::table('ministry_roles')->insertGetId(['ministry_id' => $row->ministry_id, 'name' => $name, 'created_at' => now(), 'updated_at' => now()]);
            DB::table('ministry_members')->where('id', $row->id)->update(['ministry_role_id' => $role]);
        }
    }

    public function down(): void
    {
        Schema::table('ministry_members', function (Blueprint $table) {
            $table->dropConstrainedForeignId('ministry_role_id');
            $table->dropColumn(['sort_order', 'bio']);
        });
        Schema::table('ministries', function (Blueprint $table) {
            $table->dropUnique(['key']);
            $table->dropColumn('key');
        });
        Schema::dropIfExists('ministry_roles');
    }
};
