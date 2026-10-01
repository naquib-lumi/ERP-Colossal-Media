<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Delivery orders: one per delivery location of an order, built from the
     * order's delivery_breakdowns by App\Services\DeliveryOrderService. Lines are
     * a snapshot so a printed or emailed DO keeps matching what was sent.
     */
    public function up(): void
    {
        Schema::create('delivery_orders', function (Blueprint $table) {
            $table->id();
            $table->string('do_number', 30)->nullable()->unique(); // DO-YYYY-0001, set after insert
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('location_key');                        // normalised location, for matching on update
            $table->string('location');                            // as entered
            $table->string('methods')->nullable();                 // e.g. "delivery, installation"
            $table->date('delivery_date')->nullable();              // earliest line date
            $table->time('delivery_time')->nullable();
            $table->string('status', 20)->default('issued');       // issued | cancelled
            $table->string('content_hash', 64);                     // lines fingerprint
            $table->timestamp('emailed_at')->nullable();
            $table->string('emailed_to')->nullable();
            $table->string('emailed_hash', 64)->nullable();         // content_hash when last emailed
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['order_id', 'location_key']);
        });

        Schema::create('delivery_order_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_order_id')->constrained('delivery_orders')->cascadeOnDelete();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('delivery_breakdown_id')->nullable();
            $table->string('description');                         // product name at the time
            $table->string('method', 50)->nullable();
            $table->integer('quantity')->default(0);
            $table->date('delivery_date')->nullable();
            $table->time('delivery_time')->nullable();
            $table->timestamps();

            $table->foreign('product_id')->references('ProductID')->on('products')->nullOnDelete();
            $table->foreign('delivery_breakdown_id')->references('BreakdownID')->on('delivery_breakdowns')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_order_lines');
        Schema::dropIfExists('delivery_orders');
    }
};
