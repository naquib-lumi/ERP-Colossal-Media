<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
public function up()
{
    Schema::table('reminders', function (Blueprint $table) {
        if (!Schema::hasColumn('reminders', 'created_by')) {
            $table->foreignId('created_by')->nullable()->constrained('users')->after('salesperson_id');
        }
    });
}

    public function down()
    {
        Schema::table('reminders', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropColumn('created_by');
        });
    }
};