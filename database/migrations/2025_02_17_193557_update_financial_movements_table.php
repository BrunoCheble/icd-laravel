<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('financial_movements', function (Blueprint $table) {
            $table->foreignId('member_id')->nullable()->constrained('members');
            $table->foreignId('ministry_id')->nullable()->constrained('ministries');
            $table->date('processed_date')->nullable();
            $table->string('notes')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    /**
 * Reverse the migrations.
 */
/**
 * Reverse the migrations.
 */
public function down(): void
{
    Schema::table('financial_movements', function (Blueprint $table) {
        $table->dropForeign(['member_id']);
        $table->dropColumn('member_id');
        $table->dropForeign(['ministry_id']);
        $table->dropColumn('ministry_id');
        $table->dropColumn('processed_date');
        $table->dropColumn('notes');
    });
}

};
