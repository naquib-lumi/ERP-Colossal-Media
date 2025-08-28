<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // --- PRODUCTS: add taskType & status; drop location & date_time
        Schema::table('products', function (Blueprint $table) {
            // add after existing columns (positions are best-effort)
            if (!Schema::hasColumn('products', 'taskType')) {
                $table->string('taskType', 50)->nullable()->after('productRemark');
            }
            if (!Schema::hasColumn('products', 'status')) {
                $table->string('status', 30)->default('in_progress')->after('taskType')->index();
            }
        });

        // drop old columns in a separate call to avoid some SQL dialect quirks
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'location')) {
                $table->dropColumn('location');
            }
            if (Schema::hasColumn('products', 'date_time')) {
                $table->dropColumn('date_time');
            }
        });

        // --- ORDERS: drop taskType
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'taskType')) {
                $table->dropColumn('taskType');
            }
        });
    }

    public function down(): void
    {
        // --- PRODUCTS: restore location & date_time; remove taskType & status
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'location')) {
                $table->string('location', 255)->nullable()->after('productRemark');
            }
            if (!Schema::hasColumn('products', 'date_time')) {
                $table->dateTime('date_time')->nullable()->after('location');
            }
        });

        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'status')) {
                $table->dropIndex(['status']); // safe even if missing
                $table->dropColumn('status');
            }
            if (Schema::hasColumn('products', 'taskType')) {
                $table->dropColumn('taskType');
            }
        });

        // --- ORDERS: restore taskType (as string for portability)
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'taskType')) {
                $table->string('taskType', 50)->nullable()->after('approval');
            }
        });
    }
};
