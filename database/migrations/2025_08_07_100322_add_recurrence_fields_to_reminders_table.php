<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reminders', function (Blueprint $table) {
            $table->enum('recurrence_type', ['none', 'daily', 'weekly', 'monthly'])->default('none')->after('status');
            $table->time('recurrence_time')->nullable()->after('recurrence_type');
            $table->date('end_date')->nullable()->after('recurrence_time');
            $table->boolean('is_auto')->default(false)->after('end_date');
        });
    }

    public function down(): void
    {
        Schema::table('reminders', function (Blueprint $table) {
            $table->dropColumn(['recurrence_type', 'recurrence_time', 'end_date', 'is_auto']);
        });
    }
};