<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('setlists', function (Blueprint $table) {
            $table->id();

            $table->string('title');
            $table->string('minister_name')->nullable();
            $table->date('event_date')->nullable();

            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        Schema::create('setlist_songs', function (Blueprint $table) {
            $table->unsignedBigInteger('setlist_id');
            $table->unsignedBigInteger('song_id');

            $table->primary(['setlist_id', 'song_id']);

            $table->foreign('setlist_id', 'fk_setlist_songs_setlist')
                ->references('id')->on('setlists')
                ->cascadeOnDelete();

            $table->foreign('song_id', 'fk_setlist_songs_song')
                ->references('id')->on('songs')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('setlist_songs');
        Schema::dropIfExists('setlists');
    }
};
