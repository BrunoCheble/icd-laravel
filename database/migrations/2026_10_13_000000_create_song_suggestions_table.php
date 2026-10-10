<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Chord corrections suggested on the public setlist page (each change: chord n of the map, from, to or removed),
     * waiting for an admin to approve or reject them.
     */
    public function up(): void
    {
        Schema::create('song_suggestions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('song_id')->constrained()->cascadeOnDelete();
            $table->foreignId('setlist_id')->nullable()->constrained()->nullOnDelete();
            $table->string('author', 60)->nullable();
            $table->text('note')->nullable();
            $table->json('changes');
            $table->string('status', 10)->default('pending')->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('song_suggestions');
    }
};
