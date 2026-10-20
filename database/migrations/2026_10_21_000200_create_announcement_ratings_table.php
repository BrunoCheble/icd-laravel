<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The stars (1 to 5) members give an announcement: one per member (found by the NIF), which they can change.
     */
    public function up(): void
    {
        Schema::create('announcement_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('announcement_id')->constrained('announcements')->cascadeOnDelete();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->unsignedTinyInteger('stars');
            $table->timestamps();
            $table->unique(['announcement_id', 'member_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcement_ratings');
    }
};
