<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // tinyint(1) that can be NULL
            $table->boolean('installation_task_type')
                  ->nullable()
                  ->after('taskType');

            // status text (tune length if you prefer)
            $table->string('installation_status', 50)
                  ->nullable()
                  ->after('installation_task_type');

            // tinyint(1) that can be NULL
            $table->boolean('installation_accepted')
                  ->nullable()
                  ->after('installation_status');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'installation_task_type',
                'installation_status',
                'installation_accepted',
            ]);
        });
    }
};
