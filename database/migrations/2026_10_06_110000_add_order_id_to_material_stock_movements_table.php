<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** A manual deduction may name the order the material was used for. Stock is still never deducted automatically. */
    public function up(): void
    {
        Schema::table('material_stock_movements', function (Blueprint $table) {
            $table->foreignId('order_id')->nullable()->after('user_id')->constrained('orders')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('material_stock_movements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('order_id');
        });
    }
};
