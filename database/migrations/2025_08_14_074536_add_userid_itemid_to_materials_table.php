<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            // Add columns only if they don't already exist
            if (!Schema::hasColumn('materials', 'UserID')) {
                $table->unsignedBigInteger('UserID')->nullable()->after('MaterialID');
                $table->index('UserID');
            }
            if (!Schema::hasColumn('materials', 'ItemID')) {
                $table->unsignedBigInteger('ItemID')->nullable()->after('UserID');
                $table->index('ItemID');
            }

            // FKs (use set null on delete to keep existing materials safe)
            $table->foreign('UserID')
                  ->references('id')->on('users')
                  ->nullOnDelete();

            $table->foreign('ItemID')
                  ->references('ItemID')->on('product_items')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            // Drop FKs first, then columns (guard with exists checks)
            if (Schema::hasColumn('materials', 'UserID')) {
                $table->dropForeign(['UserID']);
                $table->dropColumn('UserID');
            }
            if (Schema::hasColumn('materials', 'ItemID')) {
                $table->dropForeign(['ItemID']);
                $table->dropColumn('ItemID');
            }
        });
    }
};
