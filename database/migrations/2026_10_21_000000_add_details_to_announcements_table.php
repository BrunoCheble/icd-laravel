<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The fields of the announcement's category (see AnnouncementCategories), e.g. {"area": "cleaning", "location":
     * "Amadora"}, and the photos of a donation or a sale (up to 3 paths in the public folder).
     */
    public function up(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->json('details')->nullable()->after('type');
            $table->json('photos')->nullable()->after('image_path');
        });
    }

    public function down(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->dropColumn(['details', 'photos']);
        });
    }
};
