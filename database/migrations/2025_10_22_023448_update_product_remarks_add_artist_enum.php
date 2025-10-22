<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Modify the ENUM column to include 'artist'
        DB::statement("
            ALTER TABLE `product_remarks`
            MODIFY `operation` ENUM(
                'printing',
                'furnishing',
                'installation',
                'delivery',
                'courier',
                'self_pickup',
                'artist'
            ) NULL DEFAULT NULL;
        ");
    }

    public function down(): void
    {
        // Rollback (remove 'artist' from ENUM)
        DB::statement("
            ALTER TABLE `product_remarks`
            MODIFY `operation` ENUM(
                'printing',
                'furnishing',
                'installation',
                'delivery',
                'courier',
                'self_pickup'
            ) NULL DEFAULT NULL;
        ");
    }
};
