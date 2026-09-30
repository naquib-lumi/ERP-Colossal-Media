<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Stock on hand per material. Balances change only through
     * App\Services\MaterialStockService, which also writes material_stock_movements.
     *
     * - stock_volume: area in square inches (same unit as unitCost, RM per sq inch)
     * - stock_quantity: physical count in quantity_unit (roll, sheet, piece, ...)
     */
    public function up(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            $table->decimal('stock_quantity', 14, 2)->default(0)->after('unitCost');
            $table->string('quantity_unit', 30)->nullable()->after('stock_quantity');
            $table->decimal('stock_volume', 16, 2)->default(0)->after('quantity_unit');
            $table->decimal('low_stock_quantity', 14, 2)->nullable()->after('stock_volume');
            $table->decimal('low_stock_volume', 16, 2)->nullable()->after('low_stock_quantity');
        });
    }

    public function down(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            $table->dropColumn(['stock_quantity', 'quantity_unit', 'stock_volume', 'low_stock_quantity', 'low_stock_volume']);
        });
    }
};
