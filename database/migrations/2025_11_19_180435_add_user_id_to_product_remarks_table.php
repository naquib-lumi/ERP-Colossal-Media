<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
 public function up()
{
    if (!Schema::hasColumn('product_remarks', 'user_id')) {
        Schema::table('product_remarks', function (Blueprint $table) {
            $table->foreignId('user_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete()
                  ->after('id');
        });
    }
}

public function down()
{
    if (Schema::hasColumn('product_remarks', 'user_id')) {
        Schema::table('product_remarks', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }
}

};
