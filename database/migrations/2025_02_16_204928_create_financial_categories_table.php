<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('financial_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->decimal('expected_total', 15, 2)->nullable()->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('financial_categories');
    }
};
