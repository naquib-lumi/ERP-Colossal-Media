<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Every change to a material's stock, with the balance after it.
     * material_name is a snapshot so history survives deleting the material.
     */
    public function up(): void
    {
        Schema::create('material_stock_movements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('material_id')->nullable();
            $table->string('material_name');
            $table->string('type', 30); // App\Enums\StockMovementType

            $table->decimal('quantity_change', 14, 2)->default(0);
            $table->decimal('volume_change', 16, 2)->default(0);
            $table->decimal('quantity_after', 14, 2);
            $table->decimal('volume_after', 16, 2);

            $table->string('reason', 500)->nullable();

            $table->unsignedBigInteger('order_id')->nullable();
            $table->unsignedBigInteger('product_item_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->foreign('material_id')->references('MaterialID')->on('materials')->nullOnDelete();
            $table->foreign('order_id')->references('id')->on('orders')->nullOnDelete();
            $table->foreign('product_item_id')->references('ItemID')->on('product_items')->nullOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();

            $table->index(['material_id', 'created_at']);
            $table->index('product_item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('material_stock_movements');
    }
};
