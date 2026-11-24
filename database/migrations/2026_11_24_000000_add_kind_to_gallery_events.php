<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A gallery album is an event (a service, a baptism…, with its date) or one of the church's spaces (the entrance,
     * the hall, the kids' rooms…, without a date). Both show on the site's "Galeria": the events first, then the spaces.
     */
    public function up(): void
    {
        Schema::table('gallery_events', function (Blueprint $table) {
            $table->string('kind', 10)->default('event')->after('id');
            $table->date('event_date')->nullable()->change();
            $table->index(['kind', 'is_published']);
        });
    }

    public function down(): void
    {
        DB::table('gallery_events')->whereNull('event_date')->update(['event_date' => DB::raw('DATE(created_at)')]);
        Schema::table('gallery_events', function (Blueprint $table) {
            $table->dropIndex(['kind', 'is_published']);
            $table->dropColumn('kind');
            $table->date('event_date')->nullable(false)->change();
        });
    }
};
