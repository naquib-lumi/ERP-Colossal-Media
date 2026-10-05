<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Internal reference code for a material (e.g. supplier or shelf code), set by admin. */
    public function up(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            $table->string('internal_ref', 50)->nullable()->after('materialDescription');
        });
    }

    public function down(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            $table->dropColumn('internal_ref');
        });
    }
};
