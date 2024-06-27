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
        Schema::table('members', function (Blueprint $table) {
            $table->string('spouse_id')->nullable()->references('id')->on('members');
            $table->string('father_id')->nullable()->references('id')->on('members');
            $table->string('mother_id')->nullable()->references('id')->on('members');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn('spouse_id');
            $table->dropColumn('father_id');
            $table->dropColumn('mother_id');
        });
    }
};
