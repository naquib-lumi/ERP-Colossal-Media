<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id('ProductID');
            $table->unsignedBigInteger('OrderID'); // Foreign key to orders table
            $table->string('productName');
            $table->integer('totalQuantity');
            $table->text('materialRemark')->nullable();
            $table->text('productRemark')->nullable();
            $table->timestamps();

            // Foreign key constraint
            $table->foreign('OrderID')->references('id')->on('orders')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
