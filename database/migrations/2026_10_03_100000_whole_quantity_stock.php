<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Stock is counted in whole units only (the client decides per material
     * what 1 unit is: a roll, a sheet, a piece, a box), changed by hand only.
     *
     * Removes the square-inch volume, the automatic deduction from orders
     * (orders.stock_tracked and the order links on movements), and turns the
     * quantities into integers. Existing movements are rounded and their
     * balances rebuilt, so every material's stock equals the sum of its log.
     */
    public function up(): void
    {
        DB::table('material_stock_movements')->whereIn('type', ['order_deduct', 'order_return'])->delete();

        $balances = [];
        DB::table('material_stock_movements')->orderBy('id')->chunkById(500, function ($rows) use (&$balances) {
            foreach ($rows as $row) {
                $change = (int) round((float) $row->quantity_change);
                if ($change === 0) {
                    // volume-only movement: nothing left to record
                    DB::table('material_stock_movements')->where('id', $row->id)->delete();
                    continue;
                }

                $key = $row->material_id ?? 'none';
                $balances[$key] = ($balances[$key] ?? 0) + $change;
                DB::table('material_stock_movements')->where('id', $row->id)
                    ->update(['quantity_change' => $change, 'quantity_after' => $balances[$key]]);
            }
        });

        DB::table('materials')->update(['stock_quantity' => 0]);
        foreach ($balances as $materialId => $balance) {
            if ($materialId !== 'none') {
                DB::table('materials')->where('MaterialID', $materialId)->update(['stock_quantity' => $balance]);
            }
        }
        DB::table('materials')->whereNotNull('low_stock_quantity')
            ->update(['low_stock_quantity' => DB::raw('ROUND(low_stock_quantity)')]);

        Schema::table('materials', function (Blueprint $table) {
            $table->dropColumn(['stock_volume', 'low_stock_volume']);
        });
        Schema::table('materials', function (Blueprint $table) {
            $table->integer('stock_quantity')->default(0)->change();
            $table->unsignedInteger('low_stock_quantity')->nullable()->change();
        });

        Schema::table('material_stock_movements', function (Blueprint $table) {
            $table->dropForeign(['order_id']);
            $table->dropForeign(['product_item_id']);
            $table->dropIndex(['product_item_id']);
            $table->dropColumn(['volume_change', 'volume_after', 'order_id', 'product_item_id']);
        });
        Schema::table('material_stock_movements', function (Blueprint $table) {
            $table->integer('quantity_change')->change();
            $table->integer('quantity_after')->change();
        });

        if (Schema::hasColumn('orders', 'stock_tracked')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn('stock_tracked');
            });
        }
    }

    /** Puts the columns back; volumes and order links removed by up() are not restored. */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->boolean('stock_tracked')->default(false)->after('submit');
        });

        Schema::table('material_stock_movements', function (Blueprint $table) {
            $table->decimal('quantity_change', 14, 2)->default(0)->change();
            $table->decimal('quantity_after', 14, 2)->change();
            $table->decimal('volume_change', 16, 2)->default(0)->after('quantity_change');
            $table->decimal('volume_after', 16, 2)->default(0)->after('quantity_after');
            $table->unsignedBigInteger('order_id')->nullable()->after('reason');
            $table->unsignedBigInteger('product_item_id')->nullable()->after('order_id');
            $table->foreign('order_id')->references('id')->on('orders')->nullOnDelete();
            $table->foreign('product_item_id')->references('ItemID')->on('product_items')->nullOnDelete();
            $table->index('product_item_id');
        });

        Schema::table('materials', function (Blueprint $table) {
            $table->decimal('stock_quantity', 14, 2)->default(0)->change();
            $table->decimal('low_stock_quantity', 14, 2)->nullable()->change();
            $table->decimal('stock_volume', 16, 2)->default(0)->after('quantity_unit');
            $table->decimal('low_stock_volume', 16, 2)->nullable()->after('low_stock_quantity');
        });
    }
};
