<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('reminders', function (Blueprint $table) {
            $table->renameColumn('due_date', 'remind_at');
            $table->dropColumn(['recurrence_type', 'recurrence_time', 'end_date']);
        });
    }

    public function down()
    {
        Schema::table('reminders', function (Blueprint $table) {
            $table->renameColumn('remind_at', 'due_date');
            $table->string('recurrence_type')->nullable()->after('status');
            $table->time('recurrence_time')->nullable()->after('recurrence_type');
            $table->date('end_date')->nullable()->after('recurrence_time');
        });
    }
};