<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ---------- 1) sales_person_id -> user_id ----------
        if (Schema::hasColumn('orders', 'sales_person_id')) {
            Schema::table('orders', function (Blueprint $table) {
                try { $table->dropForeign(['sales_person_id']); } catch (\Throwable $e) {}
            });

            if (!Schema::hasColumn('orders', 'user_id')) {
                Schema::table('orders', function (Blueprint $table) {
                    $table->renameColumn('sales_person_id', 'user_id');
                });
            } else {
                Schema::table('orders', function (Blueprint $table) {
                    $table->dropColumn('sales_person_id');
                });
            }
        }

        // Add FK for user_id if not already there
        if (Schema::hasColumn('orders', 'user_id')) {
            Schema::table('orders', function (Blueprint $table) {
                try { $table->dropForeign(['user_id']); } catch (\Throwable $e) {}
                try { $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete(); } catch (\Throwable $e) {}
            });
        }

        // ---------- 2) order_date -> orderDate ----------
        if (Schema::hasColumn('orders', 'order_date') && !Schema::hasColumn('orders', 'orderDate')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->renameColumn('order_date', 'orderDate');
            });
        } elseif (Schema::hasColumn('orders', 'order_date') && Schema::hasColumn('orders', 'orderDate')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn('order_date');
            });
        }

        // ---------- 3) match ERD ----------
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'lead_id')) {
                $table->foreignId('lead_id')->nullable()->constrained('leads')->nullOnDelete();
            }
            if (!Schema::hasColumn('orders', 'leadName'))       $table->string('leadName')->nullable();
            if (!Schema::hasColumn('orders', 'leadPhone'))      $table->string('leadPhone', 30)->nullable();
            if (!Schema::hasColumn('orders', 'companyName'))    $table->string('companyName')->nullable();
            if (!Schema::hasColumn('orders', 'orderDate'))      $table->date('orderDate')->nullable();
            if (!Schema::hasColumn('orders', 'deadline'))       $table->date('deadline')->nullable();
            if (!Schema::hasColumn('orders', 'leadEmail'))      $table->string('leadEmail')->nullable();
            if (!Schema::hasColumn('orders', 'orderTitle'))     $table->string('orderTitle')->nullable();
            if (!Schema::hasColumn('orders', 'orderDetail'))    $table->text('orderDetail')->nullable();

            if (Schema::hasColumn('orders', 'status')) {
                $table->dropColumn('status');
            }
            if (!Schema::hasColumn('orders', 'orderStatus')) {
                $table->enum('orderStatus', [
                    'to_assign',
                    'assigned',
                    'in_progress',
                    'pending',
                    'completed',
                    'rejected',
                ])->default('to_assign')->index();
            }
            if (!Schema::hasColumn('orders', 'orderAttachment')) $table->string('orderAttachment')->nullable();
            if (!Schema::hasColumn('orders', 'approval'))        $table->boolean('approval')->default(false);
            if (!Schema::hasColumn('orders', 'taskType')) {
                $table->enum('taskType', ['courier','delivery','installation','self_pickup'])->nullable();
            }
            if (!Schema::hasColumn('orders', 'draft'))   $table->boolean('draft')->default(false);
            if (!Schema::hasColumn('orders', 'pending')) $table->boolean('pending')->default(true);

            if (Schema::hasColumn('orders', 'customer_name')) $table->dropColumn('customer_name');
            if (Schema::hasColumn('orders', 'amount'))        $table->dropColumn('amount');
        });
    }

    public function down(): void
    {
        // ---------- user_id -> sales_person_id ----------
        if (Schema::hasColumn('orders', 'user_id')) {
            Schema::table('orders', function (Blueprint $table) {
                try { $table->dropForeign(['user_id']); } catch (\Throwable $e) {}
            });

            if (!Schema::hasColumn('orders', 'sales_person_id')) {
                Schema::table('orders', function (Blueprint $table) {
                    $table->renameColumn('user_id', 'sales_person_id');
                });

                Schema::table('orders', function (Blueprint $table) {
                    try { $table->foreign('sales_person_id')->references('id')->on('users')->cascadeOnDelete(); } catch (\Throwable $e) {}
                });
            }
        }

        // ---------- drop ERD fields ----------
        Schema::table('orders', function (Blueprint $table) {
            // Drop FK first for lead_id
            try { $table->dropForeign(['lead_id']); } catch (\Throwable $e) {}

            foreach ([
                'lead_id','leadName','leadPhone','companyName','orderDate','deadline','leadEmail',
                'orderTitle','orderDetail','orderStatus','orderAttachment','approval','taskType',
                'draft','pending'
            ] as $col) {
                if (Schema::hasColumn('orders', $col)) { $table->dropColumn($col); }
            }

            // Restore old columns
            if (!Schema::hasColumn('orders', 'status')) {
                $table->enum('status', ['pending','processed','shipped','delivered'])->default('pending');
            }
            if (!Schema::hasColumn('orders', 'customer_name')) $table->string('customer_name');
            if (!Schema::hasColumn('orders', 'amount'))        $table->decimal('amount', 10, 2);
        });
    }
};
