<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('financial_movements', function (Blueprint $table) {
            $table->id();
            $table->string('description');
            $table->date('date');
            $table->foreignId('wallet_id')->constrained('wallets');
            $table->foreignId('category_id')->nullable()->constrained('financial_categories');
            $table->decimal('amount', 15, 2);
            $table->string('type');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('financial_movements');
    }
};
