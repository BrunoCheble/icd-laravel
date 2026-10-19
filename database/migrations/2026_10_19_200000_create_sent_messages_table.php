<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The WhatsApp messages of the day marked as sent on the home page (birthday, wedding anniversary, visitor
     * welcome, pending reminder): one row per message, so nobody sends it twice. "period" is when it counts for:
     * the year for anniversaries, "once" for a welcome, the day for a reminder.
     */
    public function up(): void
    {
        Schema::create('sent_messages', function (Blueprint $table) {
            $table->id();
            $table->string('kind', 20);
            $table->string('recipient_type', 20);
            $table->unsignedBigInteger('recipient_id');
            $table->string('period', 20);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
            $table->unique(['kind', 'recipient_type', 'recipient_id', 'period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sent_messages');
    }
};
