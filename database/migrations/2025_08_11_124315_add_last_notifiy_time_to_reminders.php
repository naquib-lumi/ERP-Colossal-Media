<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('reminders', function (Blueprint $table) {
            $table->timestamp('last_notify_time')->nullable()->after('is_auto');
        });
    }

    public function down()
    {
        Schema::table('reminders', function (Blueprint $table) {
            $table->dropColumn('last_notify_time');
        });
    }
};