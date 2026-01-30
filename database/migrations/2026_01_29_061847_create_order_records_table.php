<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_records', function (Blueprint $table) {
            $table->id();

            // FK to orders.id
            $table->unsignedBigInteger('order_id')->unique();

            // record timestamps (nullable)
            $table->dateTime('first_created_at')->nullable();
            $table->dateTime('first_edited_at')->nullable();
            $table->dateTime('submitted_at')->nullable();

            $table->timestamps();

            $table->foreign('order_id')
                  ->references('id')->on('orders')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_records');
    }
};