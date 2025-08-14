<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('delivery_breakdowns', function (Blueprint $table) {
            $table->id('BreakdownID');
            $table->unsignedBigInteger('ProductID'); // FK → products.ProductID

            $table->string('method')->nullable();    // Courier, Delivery, Installation, Self
            $table->integer('quantity')->nullable();
            $table->date('date')->nullable();
            $table->time('time')->nullable();
            $table->string('location')->nullable();

            $table->timestamps();

            $table->foreign('ProductID')
                  ->references('ProductID')->on('products')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_breakdowns');
    }
};
