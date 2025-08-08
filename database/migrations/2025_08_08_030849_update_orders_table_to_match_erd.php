<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Helper: drop a FK by inferred name if it exists
    protected function dropForeignIfExists(string $table, string $column): void
    {
        // MySQL default name is `${table}_${column}_foreign`
        $fk = "{$table}_{$column}_foreign";
        $exists = DB::selectOne("
            SELECT CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND COLUMN_NAME = ?
              AND CONSTRAINT_NAME = ?
        ", [$table, $column, $fk]);

        if ($exists) {
            Schema::table($table, function (Blueprint $table) use ($fk) {
                $table->dropForeign($fk);
            });
        }
    }

    public function up(): void
    {
        if (!Schema::hasTable('orders')) {
            return;
        }

        // --- 0) If this is already the "new" schema, NO-OP ---
        $looksNew =
            Schema::hasColumn('orders', 'user_id') &&
            Schema::hasColumn('orders', 'orderStatus') &&
            !Schema::hasColumn('orders', 'customer_name');

        if ($looksNew) {
            // Already upgraded previously or created with new schema
            return;
        }

        // --- 1) Rename sales_person_id -> user_id (legacy upgrade only) ---
        if (Schema::hasColumn('orders', 'sales_person_id')) {
            // Drop FK on sales_person_id if present
            $this->dropForeignIfExists('orders', 'sales_person_id');

            Schema::table('orders', function (Blueprint $table) {
                $table->renameColumn('sales_person_id', 'user_id');
            });

            // Re-add FK on user_id (if not already)
            Schema::table('orders', function (Blueprint $table) {
                // guard for duplicate FK
                try {
                    $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
                } catch (\Throwable $e) {
                    // ignore if already exists
                }
            });
        } elseif (!Schema::hasColumn('orders', 'user_id')) {
            // If there is no user_id at all (very old table), add it
            Schema::table('orders', function (Blueprint $table) {
                $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            });
        }

        // --- 2) order_date -> orderDate ---
        if (Schema::hasColumn('orders', 'order_date') && !Schema::hasColumn('orders', 'orderDate')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->renameColumn('order_date', 'orderDate');
            });
        } elseif (Schema::hasColumn('orders', 'order_date') && Schema::hasColumn('orders', 'orderDate')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn('order_date');
            });
        }

        // --- 3) Add / remove ERD columns ---
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'lead_id')) {
                $table->foreignId('lead_id')->nullable()->constrained('leads')->nullOnDelete();
            }

            if (!Schema::hasColumn('orders', 'leadName'))     $table->string('leadName')->nullable();
            if (!Schema::hasColumn('orders', 'leadPhone'))    $table->string('leadPhone', 30)->nullable();
            if (!Schema::hasColumn('orders', 'companyName'))  $table->string('companyName')->nullable();
            if (!Schema::hasColumn('orders', 'orderDate'))    $table->date('orderDate')->nullable();
            if (!Schema::hasColumn('orders', 'deadline'))     $table->date('deadline')->nullable();
            if (!Schema::hasColumn('orders', 'leadEmail'))    $table->string('leadEmail')->nullable();
            if (!Schema::hasColumn('orders', 'orderTitle'))   $table->string('orderTitle')->nullable();
            if (!Schema::hasColumn('orders', 'orderDetail'))  $table->text('orderDetail')->nullable();

            if (Schema::hasColumn('orders', 'status')) {
                $table->dropColumn('status');
            }

            if (!Schema::hasColumn('orders', 'orderStatus')) {
                $table->enum('orderStatus', [
                    'to_assign','assigned','in_progress','pending','completed','rejected'
                ])->default('to_assign')->index();
            }

            if (!Schema::hasColumn('orders', 'orderAttachment')) $table->string('orderAttachment')->nullable();
            if (!Schema::hasColumn('orders', 'approval'))        $table->boolean('approval')->default(false);

            if (!Schema::hasColumn('orders', 'taskType')) {
                $table->enum('taskType', ['courier','delivery','installation','self_pickup'])->nullable();
            }

            if (!Schema::hasColumn('orders', 'draft'))   $table->boolean('draft')->default(false);
            if (!Schema::hasColumn('orders', 'pending')) $table->boolean('pending')->default(true);

            // Remove legacy columns
            if (Schema::hasColumn('orders', 'customer_name')) $table->dropColumn('customer_name');
            if (Schema::hasColumn('orders', 'amount'))        $table->dropColumn('amount');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('orders')) return;

        // Drop new columns we added
        Schema::table('orders', function (Blueprint $table) {
            foreach ([
                'lead_id','leadName','leadPhone','companyName','orderDate','deadline','leadEmail',
                'orderTitle','orderDetail','orderStatus','orderAttachment','approval','taskType',
                'draft','pending'
            ] as $col) {
                if (Schema::hasColumn('orders', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        // Try to revert user_id back to sales_person_id (legacy rollback)
        if (Schema::hasColumn('orders', 'user_id') && !Schema::hasColumn('orders', 'sales_person_id')) {
            // Drop FK if exists then rename
            $this->dropForeignIfExists('orders', 'user_id');

            Schema::table('orders', function (Blueprint $table) {
                $table->renameColumn('user_id', 'sales_person_id');
            });

            Schema::table('orders', function (Blueprint $table) {
                try {
                    $table->foreign('sales_person_id')->references('id')->on('users')->cascadeOnDelete();
                } catch (\Throwable $e) {}
            });
        }

        // Restore legacy columns
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'status')) {
                $table->enum('status', ['pending','processed','shipped','delivered'])->default('pending');
            }
            if (!Schema::hasColumn('orders', 'customer_name')) $table->string('customer_name');
            if (!Schema::hasColumn('orders', 'amount'))        $table->decimal('amount', 10, 2);
        });
    }
};
