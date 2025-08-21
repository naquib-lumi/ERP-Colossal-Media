<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // --- A) materials: drop ItemID (FK no longer needed) ---
        Schema::table('materials', function (Blueprint $table) {
            if (Schema::hasColumn('materials', 'ItemID')) {
                // If a foreign key existed, drop it safely
                try { $table->dropForeign(['ItemID']); } catch (\Throwable $e) {}
                $table->dropColumn('ItemID');
            }
        });

        // --- B) product_items: replace bleedWidth/Height/Length with Top/Bottom/Left/Right ---
        Schema::table('product_items', function (Blueprint $table) {
            // Remove old bleed columns if present
            if (Schema::hasColumn('product_items', 'bleedWidth'))  $table->dropColumn('bleedWidth');
            if (Schema::hasColumn('product_items', 'bleedHeight')) $table->dropColumn('bleedHeight');
            if (Schema::hasColumn('product_items', 'bleedLength')) $table->dropColumn('bleedLength');

            // Add new bleed directional columns
            if (!Schema::hasColumn('product_items', 'bleedTop'))    $table->decimal('bleedTop', 10, 2)->nullable()->after('sizeLength');
            if (!Schema::hasColumn('product_items', 'bleedBottom')) $table->decimal('bleedBottom', 10, 2)->nullable()->after('bleedTop');
            if (!Schema::hasColumn('product_items', 'bleedLeft'))   $table->decimal('bleedLeft', 10, 2)->nullable()->after('bleedBottom');
            if (!Schema::hasColumn('product_items', 'bleedRight'))  $table->decimal('bleedRight', 10, 2)->nullable()->after('bleedLeft');
        });
    }

    public function down(): void
    {
        // Recreate ItemID (nullable) for rollback
        Schema::table('materials', function (Blueprint $table) {
            if (!Schema::hasColumn('materials', 'ItemID')) {
                $table->unsignedBigInteger('ItemID')->nullable()->after('UserID');
            }
        });

        // Revert bleed columns to the old three
        Schema::table('product_items', function (Blueprint $table) {
            if (!Schema::hasColumn('product_items', 'bleedWidth'))  $table->decimal('bleedWidth', 10, 2)->nullable()->after('sizeLength');
            if (!Schema::hasColumn('product_items', 'bleedHeight')) $table->decimal('bleedHeight', 10, 2)->nullable()->after('bleedWidth');
            if (!Schema::hasColumn('product_items', 'bleedLength')) $table->decimal('bleedLength', 10, 2)->nullable()->after('bleedHeight');

            if (Schema::hasColumn('product_items', 'bleedTop'))    $table->dropColumn('bleedTop');
            if (Schema::hasColumn('product_items', 'bleedBottom')) $table->dropColumn('bleedBottom');
            if (Schema::hasColumn('product_items', 'bleedLeft'))   $table->dropColumn('bleedLeft');
            if (Schema::hasColumn('product_items', 'bleedRight'))  $table->dropColumn('bleedRight');
        });
    }
};
