<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A ministry's page on the church site ("Conheça Nossos Ministérios"): whether and where it shows, its group
     * (a ministry or a department), icon, card (tag, title, summary) and its page (title and the text in sections,
     * see MinistryBio).
     */
    public function up(): void
    {
        Schema::table('ministries', function (Blueprint $table) {
            $table->boolean('site_visible')->default(false);
            $table->unsignedSmallInteger('site_order')->default(0);
            $table->string('site_group', 20)->default('ministries');
            $table->string('site_icon', 60)->nullable();
            $table->string('site_tag', 60)->nullable();
            $table->string('site_title', 120)->nullable();
            $table->string('site_page_title', 160)->nullable();
            $table->text('site_summary')->nullable();
            $table->json('site_page')->nullable();
            $table->index(['site_visible', 'site_order']);
        });
    }

    public function down(): void
    {
        Schema::table('ministries', function (Blueprint $table) {
            $table->dropIndex(['site_visible', 'site_order']);
            $table->dropColumn(['site_visible', 'site_order', 'site_group', 'site_icon', 'site_tag', 'site_title', 'site_page_title', 'site_summary', 'site_page']);
        });
    }
};
