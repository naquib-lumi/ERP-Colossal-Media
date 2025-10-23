<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement("
            ALTER TABLE `orders`
            MODIFY `orderStatus` ENUM(
                'to_assign',
                'assigned',
                'in_progress',
                'awaiting_keyin',
                'pending',
                'completed',
                'rejected'
            ) NOT NULL DEFAULT 'to_assign'
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE `orders`
            MODIFY `orderStatus` ENUM(
                'to_assign',
                'assigned',
                'in_progress',
                'pending',
                'completed',
                'rejected'
            ) NOT NULL DEFAULT 'to_assign'
        ");
    }
};