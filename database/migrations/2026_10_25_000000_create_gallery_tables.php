<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The site's gallery: events (a service, a baptism, a meeting…) with their photos, in order, each with an optional
     * caption; a photo can be hidden and one is the event's cover (the first one otherwise). The site shows the
     * published events, the most recent first (see ListPublicGalleryService).
     */
    public function up(): void
    {
        Schema::create('gallery_events', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->date('event_date');
            $table->boolean('is_published')->default(true);
            // One of its photos (no foreign key: the photos belong to the event); null: its first photo.
            $table->unsignedBigInteger('cover_photo_id')->nullable();
            $table->timestamps();
            $table->index(['is_published', 'event_date']);
        });

        Schema::create('gallery_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gallery_event_id')->constrained('gallery_events')->cascadeOnDelete();
            // In the public folder: the photo (at most 1600 px) and a small one for grids (at most 600 px).
            $table->string('path');
            $table->string('thumb')->nullable();
            $table->unsignedSmallInteger('width')->nullable();
            $table->unsignedSmallInteger('height')->nullable();
            $table->string('caption', 160)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_hidden')->default(false);
            $table->timestamps();
            $table->index(['gallery_event_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gallery_photos');
        Schema::dropIfExists('gallery_events');
    }
};
