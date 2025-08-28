<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('product_items', function (Blueprint $t) {
            // add after sizeHeight for readability
            $t->string('sizeUnit', 10)->default('mm')->after('sizeHeight');
            // drop unused
            $t->dropColumn(['renderTime', 'sizeLength']);
        });

        // pick a sensible default for existing rows
        DB::table('product_items')->whereNull('sizeUnit')->update(['sizeUnit' => 'mm']);
    }

    public function down(): void
    {
        Schema::table('product_items', function (Blueprint $t) {
            // revert
            $t->integer('renderTime')->nullable();
            $t->decimal('sizeLength', 10, 2)->nullable();
            $t->dropColumn('sizeUnit');
        });
    }
};