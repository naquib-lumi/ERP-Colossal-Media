<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Set the first time an order is submitted after inventory went live.
     * Only tracked orders deduct stock, so orders submitted before launch
     * never touch the (then empty) stock balances.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->boolean('stock_tracked')->default(false)->after('submit');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('stock_tracked');
        });
    }
};
