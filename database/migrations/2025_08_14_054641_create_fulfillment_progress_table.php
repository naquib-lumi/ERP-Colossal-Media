<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('fulfillment_progress', function (Blueprint $table) {
            $table->id('ProgressID');
            $table->unsignedBigInteger('ProductID'); // FK → products.ProductID

            $table->string('stage')->nullable();      // e.g. queued, printing, QC, packed…
            $table->timestamp('acceptedAt')->nullable();
            $table->timestamp('completedAt')->nullable();
            $table->string('status')->nullable();     // e.g. pending, in_progress, done

            $table->timestamps();

            $table->foreign('ProductID')
                  ->references('ProductID')->on('products')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fulfillment_progress');
    }
};
