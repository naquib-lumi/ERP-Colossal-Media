<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // --- 1. Add accepted column to products ---
        Schema::table('products', function (Blueprint $table) {
            $table->tinyInteger('accepted')->nullable()->after('status')
                  ->comment('Moved from orders table; tracks product-level acceptance');
        });

        // --- 2. Drop accepted column from orders ---
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'accepted')) {
                $table->dropColumn('accepted');
            }
        });
    }

    public function down(): void
    {
        // --- Reverse: re-add accepted to orders, remove from products ---
        Schema::table('orders', function (Blueprint $table) {
            $table->tinyInteger('accepted')->nullable()->after('orderStatus');
        });

        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'accepted')) {
                $table->dropColumn('accepted');
            }
        });
    }
};