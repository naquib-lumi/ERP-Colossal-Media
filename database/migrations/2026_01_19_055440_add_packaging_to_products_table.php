<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->tinyInteger('packaging')
                  ->nullable()      // ✅ allow NULL
                  ->default(0)      // 0 = Pending, 1 = Completed
                  ->after('editable');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('packaging');
        });
    }
};
