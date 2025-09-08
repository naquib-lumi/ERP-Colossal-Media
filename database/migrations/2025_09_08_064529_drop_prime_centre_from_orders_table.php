<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Drop the column from orders
        if (Schema::hasColumn('orders', 'prime_centre')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn('prime_centre');
            });
        }
    }

    public function down(): void
    {
        // Re-add if you ever roll back
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'prime_centre')) {
                $table->boolean('prime_centre')->default(false)->after('orderAttachment');
            }
        });
    }
};