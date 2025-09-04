<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('product_items', function (Blueprint $t) {
            // put next to bleed fields for readability
            $t->string('bleedUnit', 10)->default('mm')->after('bleedRight');
        });

        DB::table('product_items')
            ->whereNull('bleedUnit')
            ->update(['bleedUnit' => 'mm']);
    }

    public function down(): void
    {
        Schema::table('product_items', function (Blueprint $t) {
            $t->dropColumn('bleedUnit');
        });
    }
};