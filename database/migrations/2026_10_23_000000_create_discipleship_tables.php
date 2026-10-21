<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Discipleship: a member (the mentor, "responsável") follows other members (the disciples); their meetings, one
     * by one, with one or more of the mentor's disciples (who attended), whether each took place (and why not) and
     * comments. The mentor is reminded on the day and a little before (see SendPushDigestService).
     */
    public function up(): void
    {
        Schema::create('discipleships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mentor_id')->constrained('members')->cascadeOnDelete();
            $table->foreignId('disciple_id')->constrained('members')->cascadeOnDelete();
            $table->date('started_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['mentor_id', 'disciple_id']);
        });

        Schema::create('discipleship_meetings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mentor_id')->constrained('members')->cascadeOnDelete();
            $table->dateTime('scheduled_at');
            $table->string('location', 120)->nullable();
            // scheduled | held | not_held
            $table->string('status', 20)->default('scheduled');
            // Why it did not take place (see DiscipleshipMeeting::REASONS) and a note.
            $table->string('reason', 30)->nullable();
            $table->string('reason_note', 255)->nullable();
            $table->timestamp('reminded_morning_at')->nullable();
            $table->timestamp('reminded_before_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'scheduled_at']);
        });

        Schema::create('discipleship_meeting_member', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained('discipleship_meetings')->cascadeOnDelete();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            // Whether the disciple was there (null until the meeting is marked).
            $table->boolean('attended')->nullable();
            $table->unique(['meeting_id', 'member_id']);
        });

        Schema::create('discipleship_meeting_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained('discipleship_meetings')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('body');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discipleship_meeting_comments');
        Schema::dropIfExists('discipleship_meeting_member');
        Schema::dropIfExists('discipleship_meetings');
        Schema::dropIfExists('discipleships');
    }
};
