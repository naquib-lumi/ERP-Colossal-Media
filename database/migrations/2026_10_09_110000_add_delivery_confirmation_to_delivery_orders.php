<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Delivery confirmation (plan items F3/F4): a DO is marked delivered with a
     * photo of the signed DO. A delivered DO is frozen: later order changes no
     * longer update or cancel it. Every DO change is logged in
     * delivery_order_events for its history.
     */
    public function up(): void
    {
        Schema::table('delivery_orders', function (Blueprint $table) {
            $table->dateTime('delivered_at')->nullable()->after('emailed_hash');   // when it was delivered, as entered
            $table->foreignId('delivered_by')->nullable()->after('delivered_at')->constrained('users')->nullOnDelete();
            $table->text('delivery_remarks')->nullable()->after('delivered_by');
            $table->string('signed_photo_path')->nullable()->after('delivery_remarks'); // on the private "local" disk
        });

        Schema::create('delivery_order_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_order_id')->constrained('delivery_orders')->cascadeOnDelete();
            $table->string('event', 30);                    // created | updated | cancelled | emailed | delivered
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('note', 500)->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_order_events');
        Schema::table('delivery_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('delivered_by');
            $table->dropColumn(['delivered_at', 'delivery_remarks', 'signed_photo_path']);
        });
    }
};
