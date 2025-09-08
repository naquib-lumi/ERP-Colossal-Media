<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('product_items', function (Blueprint $table) {
            if (!Schema::hasColumn('product_items', 'prime_centre')) {
                $table->boolean('prime_centre')->default(false)->after('finishing');
            }
        });
    }

    public function down(): void
    {
        Schema::table('product_items', function (Blueprint $table) {
            if (Schema::hasColumn('product_items', 'prime_centre')) {
                $table->dropColumn('prime_centre');
            }
        });
    }
};