<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            // add new column for category/type
            $table->string('materialType', 100)->nullable()->after('materialName');

            // increase precision: from DECIMAL(12,2) -> DECIMAL(12,6) (supports > 4 decimals)
            $table->decimal('unitCost', 12, 6)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            $table->dropColumn('materialType');
            $table->decimal('unitCost', 12, 2)->nullable()->change();
        });
    }
};