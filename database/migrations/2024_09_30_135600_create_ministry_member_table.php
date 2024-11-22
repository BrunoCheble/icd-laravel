<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMinistryMemberTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ministry_member', function (Blueprint $table) {
            $table->id(); // id field (auto-increment primary key)

            // Foreign keys for ministries and members
            $table->foreignId('ministry_id')->constrained()->onDelete('cascade'); // Ministry ID
            $table->foreignId('member_id')->constrained()->onDelete('cascade');   // Member ID
            $table->string('role'); // Role of member in ministry
            $table->boolean('active')->default(true); // Is member active in ministry

            $table->timestamps(); // created_at and updated_at fields
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ministry_member');
    }
}
