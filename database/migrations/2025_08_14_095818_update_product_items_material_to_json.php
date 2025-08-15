<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('product_items', function (Blueprint $table) {
            // Drop the FK/column if it exists
            if (Schema::hasColumn('product_items', 'MaterialID')) {
                $table->dropForeign(['MaterialID']); // if FK exists
                $table->dropColumn('MaterialID');
            }

            // Change "material" to JSON and nullable (create if missing)
            if (Schema::hasColumn('product_items', 'material')) {
                $table->json('material')->nullable()->change();
            } else {
                $table->json('material')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('product_items', function (Blueprint $table) {
            // Revert JSON back to string if you must roll back
            if (Schema::hasColumn('product_items', 'material')) {
                $table->text('material')->nullable()->change();
            }
            // Restore MaterialID stub (no FK re-created here)
            if (!Schema::hasColumn('product_items', 'MaterialID')) {
                $table->unsignedBigInteger('MaterialID')->nullable()->after('ProductID');
            }
        });
    }
};