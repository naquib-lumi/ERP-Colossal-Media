<?php

namespace App\Console\Commands;

use App\Models\MaterialStockMovement;
use App\Services\OrderStockSync;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Safety net for order stock: re-syncs every tracked order so the stock log
 * matches the orders even if some code path changed an order without syncing.
 * Safe to run any time; it only records differences.
 */
class ReconcileInventory extends Command
{
    protected $signature = 'inventory:reconcile {--order= : Only this order id}';

    protected $description = 'Re-sync material stock used by tracked orders and report any corrections';

    public function handle(OrderStockSync $sync): int
    {
        $before = MaterialStockMovement::max('id') ?? 0;

        $query = DB::table('orders')->where('stock_tracked', 1)->orderBy('id');
        if ($this->option('order')) {
            $query->where('id', (int) $this->option('order'));
        }

        $orders = 0;
        $query->chunkById(200, function ($rows) use ($sync, &$orders) {
            foreach ($rows as $row) {
                $sync->syncOrder((int) $row->id, note: 'stock check');
                $orders++;
            }
        });

        $corrections = MaterialStockMovement::where('id', '>', $before)->count();
        $this->info("Checked {$orders} tracked order(s); {$corrections} correction(s) recorded.");

        if ($corrections > 0) {
            // Something changed an order without syncing stock; worth finding that code path.
            logger()->warning('inventory:reconcile recorded corrections', ['count' => $corrections, 'after_movement_id' => $before]);
        }

        return self::SUCCESS;
    }
}
