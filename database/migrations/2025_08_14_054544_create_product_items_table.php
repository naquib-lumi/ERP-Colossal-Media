<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('product_items', function (Blueprint $table) {
            $table->id('ItemID');
            $table->unsignedBigInteger('ProductID');    // FK → products.ProductID
            $table->unsignedBigInteger('MaterialID')->nullable(); // optional FK → materials

            $table->string('itemName')->nullable();
            $table->integer('quantity')->nullable();

            // sizes (inches/mm—adjust as needed)
            $table->decimal('sizeWidth', 10, 2)->nullable();
            $table->decimal('sizeHeight', 10, 2)->nullable();
            $table->decimal('sizeLength', 10, 2)->nullable();

            // bleed
            $table->decimal('bleedWidth', 10, 2)->nullable();
            $table->decimal('bleedHeight', 10, 2)->nullable();
            $table->decimal('bleedLength', 10, 2)->nullable();

            $table->string('finishing')->nullable();   // free text or enum later
            $table->integer('renderTime')->nullable(); // minutes (from ERD “renderTime”)
            $table->timestamps();

            $table->foreign('ProductID')
                  ->references('ProductID')->on('products')
                  ->onDelete('cascade');

            $table->foreign('MaterialID')
                  ->references('MaterialID')->on('materials')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_items');
    }
};
